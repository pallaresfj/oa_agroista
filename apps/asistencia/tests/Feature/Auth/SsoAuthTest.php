<?php

use App\Models\User;
use Agroista\Core\Sso\OidcClient;
use Database\Seeders\PanelAccessSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('sso.session_check_enabled', true);
    $this->seed(RoleSeeder::class);
    $this->seed(PanelAccessSeeder::class);
    $this->seed(RolePermissionSeeder::class);
});

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

    $response->assertRedirect('/app/login');
    $this->assertGuest();
});

it('creates and authenticates user with valid sso callback', function () {
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
            'email' => 'docente@iedagropivijay.edu.co',
            'name' => 'Docente SSO',
            'is_active' => true,
        ]);

    $response = $this
        ->withSession([
            'sso.state' => 'expected-state',
            'sso.nonce' => 'nonce-123',
            'sso.code_verifier' => 'verifier-123',
        ])
        ->get('/sso/callback?code=auth-code&state=expected-state');

    $response->assertRedirect('/app');
    $this->assertAuthenticated();

    $user = User::query()->where('email', 'docente@iedagropivijay.edu.co')->first();
    expect($user)->not->toBeNull();
    expect($user?->is_active)->toBeTrue();
});

it('rejects login when local user is inactive', function () {
    User::query()->create([
        'name' => 'Docente',
        'email' => 'inactivo@iedagropivijay.edu.co',
        'password' => bcrypt('secret'),
        'is_active' => false,
    ])->assignRole('docente');

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
            'email' => 'inactivo@iedagropivijay.edu.co',
            'name' => 'Inactivo',
            'is_active' => true,
        ]);

    $response = $this
        ->withSession([
            'sso.state' => 'expected-state',
            'sso.nonce' => 'nonce-123',
            'sso.code_verifier' => 'verifier-123',
        ])
        ->get('/sso/callback?code=auth-code&state=expected-state');

    $response->assertRedirect('/app/login');
    $this->assertGuest();
});

it('middleware redirects authenticated panel requests to session check', function () {
    $user = User::factory()->create([
        'email' => 'docente@iedagropivijay.edu.co',
        'is_active' => true,
    ]);
    $user->assignRole('docente');

    $response = $this
        ->actingAs($user, 'web')
        ->withSession([
            'sso.session_check.last_checked_at' => now()->subMinutes(5)->timestamp,
        ])
        ->get('/app');

    $response->assertRedirect('/sso/session-check/start');
});

it('stores safe return_to from query when starting silent session check', function () {
    $user = User::factory()->create([
        'email' => 'docente@iedagropivijay.edu.co',
        'is_active' => true,
    ]);
    $user->assignRole('docente');

    $mock = Mockery::mock(OidcClient::class);
    $this->app->instance(OidcClient::class, $mock);

    $mock->shouldReceive('buildAuthorizationUrl')
        ->once()
        ->andReturn('http://localhost:9000/oauth/authorize?prompt=none');

    $response = $this
        ->actingAs($user, 'web')
        ->get('/sso/session-check/start?return_to='.urlencode('/app/records'));

    $response->assertRedirect('http://localhost:9000/oauth/authorize?prompt=none');
    expect(session('sso.session_check.return_to'))->toBe(url('/app/records'));
});

it('ignores unsafe return_to from query when starting silent session check', function () {
    $user = User::factory()->create([
        'email' => 'docente@iedagropivijay.edu.co',
        'is_active' => true,
    ]);
    $user->assignRole('docente');

    $existingReturnTo = 'http://localhost/app';

    $mock = Mockery::mock(OidcClient::class);
    $this->app->instance(OidcClient::class, $mock);

    $mock->shouldReceive('buildAuthorizationUrl')
        ->once()
        ->andReturn('http://localhost:9000/oauth/authorize?prompt=none');

    $response = $this
        ->actingAs($user, 'web')
        ->withSession(['sso.session_check.return_to' => $existingReturnTo])
        ->get('/sso/session-check/start?return_to='.urlencode('https://evil.example/app'));

    $response->assertRedirect('http://localhost:9000/oauth/authorize?prompt=none');
    expect(session('sso.session_check.return_to'))->toBe($existingReturnTo);
});

it('logs out local session when silent session check returns login_required', function () {
    $user = User::factory()->create([
        'email' => 'docente@iedagropivijay.edu.co',
        'is_active' => true,
    ]);
    $user->assignRole('docente');

    $response = $this
        ->actingAs($user, 'web')
        ->withSession([
            'sso.session_check.in_progress' => true,
            'sso.session_check.return_to' => 'http://localhost/app',
        ])
        ->get('/sso/session-check/callback?error=login_required');

    $response->assertRedirect('/app/login');
    $response->assertSessionHasErrors('sso');
    $this->assertGuest('web');
});

it('logs out local session when silent session check returns unexpected error', function () {
    $user = User::factory()->create([
        'email' => 'docente@iedagropivijay.edu.co',
        'is_active' => true,
    ]);
    $user->assignRole('docente');

    $response = $this
        ->actingAs($user, 'web')
        ->withSession([
            'sso.session_check.in_progress' => true,
            'sso.session_check.return_to' => 'http://localhost/app',
        ])
        ->get('/sso/session-check/callback?error=account_selection_required');

    $response->assertRedirect('/app/login');
    $response->assertSessionHasErrors('sso');
    $this->assertGuest('web');
});
