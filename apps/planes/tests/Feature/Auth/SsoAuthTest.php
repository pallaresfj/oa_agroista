<?php

use App\Models\User;
use Agroista\Core\Sso\OidcClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

afterEach(function (): void {
    Mockery::close();
});

it('rejects callback when state does not match', function () {
    $response = $this
        ->withSession([
            'sso.state' => 'expected-state',
            'sso.nonce' => 'nonce-123',
            'sso.code_verifier' => 'verifier-123',
        ])
        ->get('/sso/callback?code=abc&state=bad-state');

    $response->assertRedirect('/admin/login');
    $this->assertGuest();
});

it('creates user, assigns docente role and authenticates with valid sso callback', function () {
    Role::query()->create([
        'name' => 'Docente',
        'guard_name' => 'web',
    ]);

    $mock = Mockery::mock(OidcClient::class);
    $this->app->instance(OidcClient::class, $mock);

    $mock->shouldReceive('exchangeCodeForTokens')
        ->once()
        ->with('auth-code', 'verifier-123')
        ->andReturn([
            'id_token' => 'id-token',
            'access_token' => 'access-token',
        ]);

    $mock->shouldReceive('validateIdToken')
        ->once()
        ->with('id-token')
        ->andReturn([
            'nonce' => 'nonce-123',
        ]);

    $mock->shouldReceive('resolveClaims')
        ->once()
        ->andReturn([
            'sub' => 'subject-1',
            'email' => 'Docente@iedagropivijay.edu.co',
            'name' => 'Docente SSO',
            'is_active' => true,
            'picture' => 'https://avatars.example.com/docente.jpg',
        ]);

    $response = $this
        ->withSession([
            'sso.state' => 'expected-state',
            'sso.nonce' => 'nonce-123',
            'sso.code_verifier' => 'verifier-123',
        ])
        ->get('/sso/callback?code=auth-code&state=expected-state');

    $response->assertRedirect('/admin');
    $this->assertAuthenticated();

    $user = User::query()->where('email', 'docente@iedagropivijay.edu.co')->first();
    expect($user)->not->toBeNull();
    expect($user?->hasRole('Docente'))->toBeTrue();
    expect($user?->google_avatar_url)->toBe('https://avatars.example.com/docente.jpg');
});

it('assigns soporte role and syncs permissions for configured support emails', function () {
    config()->set('sso.support_emails', ['pallaresfj@iedagropivijay.edu.co']);

    Permission::query()->create([
        'name' => 'manage_all_records',
        'guard_name' => 'web',
    ]);

    Role::query()->create([
        'name' => 'Docente',
        'guard_name' => 'web',
    ]);

    $mock = Mockery::mock(OidcClient::class);
    $this->app->instance(OidcClient::class, $mock);

    $mock->shouldReceive('exchangeCodeForTokens')
        ->once()
        ->with('auth-code', 'verifier-123')
        ->andReturn([
            'id_token' => 'id-token',
            'access_token' => 'access-token',
        ]);

    $mock->shouldReceive('validateIdToken')
        ->once()
        ->with('id-token')
        ->andReturn([
            'nonce' => 'nonce-123',
        ]);

    $mock->shouldReceive('resolveClaims')
        ->once()
        ->andReturn([
            'sub' => 'subject-support',
            'email' => 'PALLARESFJ@iedagropivijay.edu.co',
            'name' => 'Soporte SSO',
            'is_active' => true,
        ]);

    $response = $this
        ->withSession([
            'sso.state' => 'expected-state',
            'sso.nonce' => 'nonce-123',
            'sso.code_verifier' => 'verifier-123',
        ])
        ->get('/sso/callback?code=auth-code&state=expected-state');

    $response->assertRedirect('/admin');
    $this->assertAuthenticated();

    $user = User::query()->where('email', 'pallaresfj@iedagropivijay.edu.co')->first();
    expect($user)->not->toBeNull();
    expect($user?->hasRole('Soporte'))->toBeTrue();
    expect($user?->hasRole('Docente'))->toBeFalse();
    expect($user?->can('manage_all_records'))->toBeTrue();
    expect($user?->can('panel_user'))->toBeTrue();
    expect($user?->can('view_any_plan'))->toBeTrue();
});

it('starts silent idp session check for authenticated users', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $mock = Mockery::mock(OidcClient::class);
    $this->app->instance(OidcClient::class, $mock);

    $mock->shouldReceive('buildAuthorizationUrl')
        ->once()
        ->with(
            Mockery::type('string'),
            Mockery::type('string'),
            Mockery::type('string'),
            'none',
            url('/sso/session-check/callback'),
        )
        ->andReturn('https://idp.example/authorize');

    $response = $this->get('/sso/session-check/start');

    $response->assertRedirect('https://idp.example/authorize');
    $response->assertSessionHas('sso.session_check.in_progress', true);
});

it('logs out local session when idp silent check reports login_required', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this
        ->withSession([
            'sso.session_check.in_progress' => true,
            'sso.session_check.started_at' => now()->timestamp,
            'sso.session_check.return_to' => 'https://gestionplanes.test/admin',
        ])
        ->get('/sso/session-check/callback?error=login_required');

    $response->assertRedirect('/admin/login');
    $this->assertGuest();
});

it('logs out local session on valid frontchannel logout request', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $client = 'gestionplanes';
    $secret = 'frontchannel-secret';
    $timestamp = now()->timestamp;
    $next = url('/admin/login');

    config()->set('sso.frontchannel_logout_client_key', $client);
    config()->set('sso.frontchannel_logout_secret', $secret);
    config()->set('sso.frontchannel_logout_ttl_seconds', 120);
    config()->set('sso.frontchannel_logout_next_hosts', [parse_url($next, PHP_URL_HOST)]);

    $signature = hash_hmac('sha256', $client.'|'.$timestamp.'|'.$next, $secret);

    $response = $this->get('/sso/frontchannel-logout?client='.$client.'&ts='.$timestamp.'&next='.urlencode($next).'&sig='.$signature);

    $response->assertRedirect($next);
    $this->assertGuest();
});
