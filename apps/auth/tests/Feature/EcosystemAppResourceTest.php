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
