<?php

namespace Tests\Feature;

use App\Filament\Resources\EcosystemApps\EcosystemAppResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EcosystemAppResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_app_autogenerates_uris_from_base_url_when_empty(): void
    {
        $client = EcosystemAppResource::createApp([
            'name' => 'Asistencia',
            'slug' => 'asistencia',
            'base_url' => 'https://oa-asistencia.test',
            'redirect_uris' => [],
            'frontchannel_logout_uris' => [],
            'scopes' => ['openid', 'email', 'profile'],
            'is_active' => true,
            'revoked' => false,
        ]);

        $this->assertSame(
            [
                'https://oa-asistencia.test/sso/callback',
                'https://oa-asistencia.test/sso/session-check/callback',
            ],
            $client->redirect_uris,
        );
        $this->assertSame(
            ['https://oa-asistencia.test/sso/frontchannel-logout'],
            $client->frontchannel_logout_uris,
        );
    }

    public function test_create_app_accepts_string_inputs_for_redirect_uris_and_scopes(): void
    {
        $client = EcosystemAppResource::createApp([
            'name' => 'Cliente de prueba',
            'slug' => 'cliente-prueba',
            'base_url' => 'https://oa-silo.test',
            'redirect_uris' => "http://localhost/callback,\nhttps://oa-silo.test/sso/callback",
            'frontchannel_logout_uris' => 'http://localhost/frontchannel-logout',
            'scopes' => "openid,email\nprofile",
            'is_active' => true,
            'revoked' => false,
        ]);

        $this->assertSame(
            ['http://localhost/callback', 'https://oa-silo.test/sso/callback'],
            $client->redirect_uris,
        );
        $this->assertSame(['openid', 'email', 'profile'], $client->scopes);
        $this->assertSame('cliente-prueba', $client->slug);
        $this->assertSame(['http://localhost/frontchannel-logout'], $client->frontchannel_logout_uris);

        $this->assertDatabaseHas('oauth_clients', [
            'id' => $client->getKey(),
            'name' => 'Cliente de prueba',
            'slug' => 'cliente-prueba',
            'revoked' => false,
        ]);
    }

    public function test_update_app_autogenerates_uris_from_base_url_when_lists_are_empty(): void
    {
        $client = EcosystemAppResource::createApp([
            'name' => 'Planes',
            'slug' => 'planes',
            'base_url' => 'https://oa-planes.test',
            'redirect_uris' => ['https://oa-planes.test/sso/callback'],
            'frontchannel_logout_uris' => ['https://oa-planes.test/sso/frontchannel-logout'],
            'scopes' => ['openid', 'email', 'profile'],
            'is_active' => true,
            'revoked' => false,
        ]);

        $updated = EcosystemAppResource::updateApp($client->fresh(), [
            'name' => 'Planes',
            'slug' => 'planes',
            'base_url' => 'https://oa-asistencia.test',
            'redirect_uris' => [],
            'frontchannel_logout_uris' => [],
            'scopes' => ['openid', 'email', 'profile'],
            'is_active' => true,
            'revoked' => false,
        ])->fresh();

        $this->assertSame(
            [
                'https://oa-asistencia.test/sso/callback',
                'https://oa-asistencia.test/sso/session-check/callback',
            ],
            $updated->redirect_uris,
        );
        $this->assertSame(
            ['https://oa-asistencia.test/sso/frontchannel-logout'],
            $updated->frontchannel_logout_uris,
        );
    }

    public function test_update_app_preserves_manual_override_when_uris_are_provided(): void
    {
        $client = EcosystemAppResource::createApp([
            'name' => 'Planes',
            'slug' => 'planes-manual',
            'base_url' => 'https://oa-planes.test',
            'redirect_uris' => ['https://oa-planes.test/sso/callback'],
            'frontchannel_logout_uris' => ['https://oa-planes.test/sso/frontchannel-logout'],
            'scopes' => ['openid', 'email', 'profile'],
            'is_active' => true,
            'revoked' => false,
        ]);

        $manualRedirects = ['https://oa-planes.test/oauth/callback-custom'];
        $manualFrontchannel = ['https://oa-planes.test/logout/frontchannel-custom'];

        $updated = EcosystemAppResource::updateApp($client->fresh(), [
            'name' => 'Planes',
            'slug' => 'planes-manual',
            'base_url' => 'https://oa-asistencia.test',
            'redirect_uris' => $manualRedirects,
            'frontchannel_logout_uris' => $manualFrontchannel,
            'scopes' => ['openid', 'email', 'profile'],
            'is_active' => true,
            'revoked' => false,
        ])->fresh();

        $this->assertSame($manualRedirects, $updated->redirect_uris);
        $this->assertSame($manualFrontchannel, $updated->frontchannel_logout_uris);
    }

    public function test_redirect_uri_host_validation_error_mentions_whitelist_and_runtime_config_hint(): void
    {
        config()->set('sso.allowed_redirect_hosts', ['oa-planes.test']);
        config()->set('sso.insecure_redirect_hosts', []);

        try {
            EcosystemAppResource::sanitizeRedirectUris(['https://oa-asistencia.test/sso/callback']);
            $this->fail('Se esperaba ValidationException por host no permitido.');
        } catch (ValidationException $exception) {
            $messages = $exception->errors()['redirect_uris'] ?? [];
            $message = implode(' ', $messages);

            $this->assertStringContainsString('No se guardó: host no permitido', $message);
            $this->assertStringContainsString('SSO_ALLOWED_REDIRECT_HOSTS', $message);
            $this->assertStringContainsString('oa-planes.test', $message);
            $this->assertStringContainsString('optimize:clear', $message);
            $this->assertStringContainsString('config:cache', $message);
        }
    }

    public function test_delete_app_removes_related_tokens_and_auth_codes(): void
    {
        $client = EcosystemAppResource::createApp([
            'name' => 'Cliente para eliminar',
            'slug' => 'cliente-eliminar',
            'base_url' => 'http://localhost',
            'redirect_uris' => ['http://localhost/callback'],
            'frontchannel_logout_uris' => [],
            'scopes' => ['openid', 'email', 'profile'],
            'is_active' => true,
            'revoked' => false,
        ]);

        DB::table('oauth_access_tokens')->insert([
            'id' => 'access-token-1',
            'user_id' => null,
            'client_id' => $client->getKey(),
            'name' => 'Token prueba',
            'scopes' => json_encode(['openid']),
            'revoked' => false,
            'created_at' => now(),
            'updated_at' => now(),
            'expires_at' => now()->addHour(),
        ]);

        DB::table('oauth_refresh_tokens')->insert([
            'id' => 'refresh-token-1',
            'access_token_id' => 'access-token-1',
            'revoked' => false,
            'expires_at' => now()->addHour(),
        ]);

        DB::table('oauth_auth_codes')->insert([
            'id' => 'auth-code-1',
            'user_id' => 1,
            'client_id' => $client->getKey(),
            'scopes' => json_encode(['openid']),
            'revoked' => false,
            'expires_at' => now()->addHour(),
        ]);

        $deleted = EcosystemAppResource::deleteApp($client->fresh());

        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('oauth_clients', ['id' => $client->getKey()]);
        $this->assertDatabaseMissing('oauth_access_tokens', ['id' => 'access-token-1']);
        $this->assertDatabaseMissing('oauth_refresh_tokens', ['id' => 'refresh-token-1']);
        $this->assertDatabaseMissing('oauth_auth_codes', ['id' => 'auth-code-1']);
    }

    public function test_generate_frontchannel_secret_entry_uses_valid_format(): void
    {
        $entry = EcosystemAppResource::generateFrontchannelSecretEntry('Planes-App');

        [$clientKey, $secret] = explode('|', $entry, 2);

        $this->assertSame('planes-app', $clientKey);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $secret);
    }

    public function test_generate_frontchannel_secret_entry_rejects_invalid_key(): void
    {
        $this->expectException(ValidationException::class);

        EcosystemAppResource::generateFrontchannelSecretEntry('planes app');
    }
}
