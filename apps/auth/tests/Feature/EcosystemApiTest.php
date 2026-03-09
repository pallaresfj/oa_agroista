<?php

namespace Tests\Feature;

use App\Models\InstitutionSetting;
use App\Models\OAuthClient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class EcosystemApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_institution_returns_singleton_settings_shape(): void
    {
        InstitutionSetting::query()->create([
            'key' => 'name',
            'type' => 'string',
            'value_text' => 'IED Agropecuaria',
            'is_public' => true,
        ]);

        InstitutionSetting::query()->create([
            'key' => 'nit',
            'type' => 'string',
            'value_text' => '123456789-0',
            'is_public' => true,
        ]);

        InstitutionSetting::query()->create([
            'key' => 'logo_url',
            'type' => 'string',
            'value_text' => 'https://example.com/logo.png',
            'is_public' => true,
        ]);

        InstitutionSetting::query()->create([
            'key' => 'color_palette',
            'type' => 'json',
            'value_json' => [
                'primary' => '#f50404',
                'success' => '#00c853',
                'info' => '#0288d1',
                'warning' => '#ff9800',
                'danger' => '#b71c1c',
            ],
            'is_public' => true,
        ]);

        $user = User::factory()->create();
        Passport::actingAs($user, ['ecosystem.read']);

        $this->getJson('/api/ecosystem/institution')
            ->assertOk()
            ->assertJsonPath('name', 'IED Agropecuaria')
            ->assertJsonPath('logo_url', 'https://example.com/logo.png')
            ->assertJsonPath('settings.nit', '123456789-0')
            ->assertJsonPath('settings.color_palette.primary', '#f50404');
    }

    public function test_update_institution_requires_superadmin_role_and_persists_values(): void
    {
        $user = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@iedagropivijay.edu.co',
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        Passport::actingAs($user, ['ecosystem.write']);

        $payload = [
            'name' => 'IED Jose Maria Herrera',
            'nit' => '901.000.123-4',
            'logo_url' => 'https://example.com/new-logo.png',
            'color_palette' => [
                'primary' => '#f50404',
                'success' => '#00c853',
                'info' => '#0288d1',
                'warning' => '#ff9800',
                'danger' => '#b71c1c',
            ],
        ];

        $this->putJson('/api/ecosystem/institution', $payload)
            ->assertOk()
            ->assertJsonPath('institution.name', 'IED Jose Maria Herrera')
            ->assertJsonPath('institution.settings.nit', '901.000.123-4');

        $this->assertDatabaseHas('institution_settings', [
            'key' => 'name',
            'value_text' => 'IED Jose Maria Herrera',
        ]);

        $this->assertDatabaseHas('institution_settings', [
            'key' => 'nit',
            'value_text' => '901.000.123-4',
        ]);
    }

    public function test_get_apps_reads_from_oauth_clients(): void
    {
        OAuthClient::query()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Planes',
            'slug' => 'planes',
            'base_url' => 'https://oa-planes.test',
            'secret' => \Illuminate\Support\Str::random(40),
            'provider' => null,
            'redirect_uris' => ['https://oa-planes.test/sso/callback'],
            'frontchannel_logout_uris' => ['https://oa-planes.test/sso/frontchannel-logout'],
            'grant_types' => ['authorization_code', 'refresh_token'],
            'scopes' => ['openid', 'email', 'profile', 'ecosystem.read'],
            'is_active' => true,
            'revoked' => false,
        ]);

        OAuthClient::query()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Inactiva',
            'slug' => 'inactiva',
            'base_url' => 'https://oa-inactiva.test',
            'secret' => \Illuminate\Support\Str::random(40),
            'provider' => null,
            'redirect_uris' => ['https://oa-inactiva.test/sso/callback'],
            'frontchannel_logout_uris' => [],
            'grant_types' => ['authorization_code', 'refresh_token'],
            'scopes' => ['openid'],
            'is_active' => false,
            'revoked' => false,
        ]);

        $user = User::factory()->create();
        Passport::actingAs($user, ['ecosystem.read']);

        $this->getJson('/api/ecosystem/apps')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.slug', 'planes')
            ->assertJsonPath('0.base_url', 'https://oa-planes.test')
            ->assertJsonPath('0.redirect_uris.0', 'https://oa-planes.test/sso/callback')
            ->assertJsonPath('0.frontchannel_logout_uris.0', 'https://oa-planes.test/sso/frontchannel-logout');
    }
}
