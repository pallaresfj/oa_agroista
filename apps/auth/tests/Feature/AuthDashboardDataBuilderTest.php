<?php

namespace Tests\Feature;

use App\Filament\Resources\EcosystemApps\EcosystemAppResource;
use App\Models\OAuthClient;
use App\Support\Dashboard\AuthDashboardDataBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthDashboardDataBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        EcosystemAppResource::flushDashboardCache();
    }

    public function test_ecosystem_card_prefers_base_url_for_external_link_and_host(): void
    {
        OAuthClient::query()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Asistencia',
            'slug' => 'asistencia',
            'base_url' => 'https://asistencia.iedagropivijay.edu.co/',
            'secret' => Str::random(40),
            'provider' => null,
            'redirect_uris' => ['https://oa-asistencia.test/sso/callback'],
            'frontchannel_logout_uris' => ['https://oa-asistencia.test/sso/frontchannel-logout'],
            'grant_types' => ['authorization_code', 'refresh_token'],
            'scopes' => ['openid', 'email', 'profile'],
            'is_active' => true,
            'revoked' => false,
        ]);

        $payload = app(AuthDashboardDataBuilder::class)->build('30d');
        $card = collect($payload['ecosystem'])->firstWhere('name', 'Asistencia');

        $this->assertIsArray($card);
        $this->assertSame('https://asistencia.iedagropivijay.edu.co', $card['externalUrl']);
        $this->assertSame('asistencia.iedagropivijay.edu.co', $card['host']);
    }

    public function test_ecosystem_card_falls_back_to_redirect_uri_when_base_url_is_missing(): void
    {
        OAuthClient::query()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Lectura',
            'slug' => 'lectura',
            'base_url' => null,
            'secret' => Str::random(40),
            'provider' => null,
            'redirect_uris' => ['https://lectura.iedagropivijay.edu.co/sso/callback'],
            'frontchannel_logout_uris' => ['https://lectura.iedagropivijay.edu.co/sso/frontchannel-logout'],
            'grant_types' => ['authorization_code', 'refresh_token'],
            'scopes' => ['openid', 'email', 'profile'],
            'is_active' => true,
            'revoked' => false,
        ]);

        $payload = app(AuthDashboardDataBuilder::class)->build('30d');
        $card = collect($payload['ecosystem'])->firstWhere('name', 'Lectura');

        $this->assertIsArray($card);
        $this->assertSame('https://lectura.iedagropivijay.edu.co/sso/callback', $card['externalUrl']);
        $this->assertSame('lectura.iedagropivijay.edu.co', $card['host']);
    }

    public function test_dashboard_cache_is_invalidated_when_an_app_is_updated(): void
    {
        $client = OAuthClient::query()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Planes',
            'slug' => 'planes',
            'base_url' => 'https://oa-planes.test',
            'secret' => Str::random(40),
            'provider' => null,
            'redirect_uris' => ['https://oa-planes.test/sso/callback'],
            'frontchannel_logout_uris' => ['https://oa-planes.test/sso/frontchannel-logout'],
            'grant_types' => ['authorization_code', 'refresh_token'],
            'scopes' => ['openid', 'email', 'profile'],
            'is_active' => true,
            'revoked' => false,
        ]);

        $builder = app(AuthDashboardDataBuilder::class);
        $initialPayload = $builder->build('30d');
        $initialCard = collect($initialPayload['ecosystem'])->firstWhere('name', 'Planes');
        $this->assertSame('https://oa-planes.test', $initialCard['externalUrl']);

        EcosystemAppResource::updateApp($client->fresh(), [
            'name' => 'Planes',
            'slug' => 'planes',
            'base_url' => 'https://oa-asistencia.test',
            'redirect_uris' => [],
            'frontchannel_logout_uris' => [],
            'scopes' => ['openid', 'email', 'profile'],
            'is_active' => true,
            'revoked' => false,
        ]);

        $updatedPayload = $builder->build('30d');
        $updatedCard = collect($updatedPayload['ecosystem'])->firstWhere('name', 'Planes');

        $this->assertSame('https://oa-asistencia.test', $updatedCard['externalUrl']);
        $this->assertSame('oa-asistencia.test', $updatedCard['host']);
    }
}
