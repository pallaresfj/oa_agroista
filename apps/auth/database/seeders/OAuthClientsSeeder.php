<?php

namespace Database\Seeders;

use App\Models\OAuthClient;
use Illuminate\Database\Seeder;
use Laravel\Passport\ClientRepository;

class OAuthClientsSeeder extends Seeder
{
    public function run(): void
    {
        /** @var ClientRepository $clients */
        $clients = app(ClientRepository::class);

        $definitions = [
            [
                'slug' => 'planes',
                'name' => 'Planes',
                'base_url' => (string) env('PLANES_BASE_URL', 'https://oa-planes.test'),
                'redirect_uris' => [
                    (string) env('PLANES_REDIRECT_URI', 'https://oa-planes.test/sso/callback'),
                    'https://oa-planes.test/sso/session-check/callback',
                ],
                'frontchannel_logout_uris' => [
                    (string) env('PLANES_FRONTCHANNEL_LOGOUT_URI', 'https://oa-planes.test/sso/frontchannel-logout'),
                ],
                'scopes' => ['openid', 'email', 'profile', 'ecosystem.read'],
            ],
            [
                'slug' => 'asistencia',
                'name' => 'Asistencia',
                'base_url' => (string) env('ASISTENCIA_BASE_URL', 'https://oa-asistencia.test'),
                'redirect_uris' => [
                    (string) env('ASISTENCIA_REDIRECT_URI', 'https://oa-asistencia.test/sso/callback'),
                    'https://oa-asistencia.test/sso/session-check/callback',
                ],
                'frontchannel_logout_uris' => [
                    (string) env('ASISTENCIA_FRONTCHANNEL_LOGOUT_URI', 'https://oa-asistencia.test/sso/frontchannel-logout'),
                ],
                'scopes' => ['openid', 'email', 'profile', 'ecosystem.read'],
            ],
            [
                'slug' => 'silo',
                'name' => 'Silo',
                'base_url' => (string) env('SILO_BASE_URL', 'https://oa-silo.test'),
                'redirect_uris' => [
                    (string) env('SILO_REDIRECT_URI', 'https://oa-silo.test/sso/callback'),
                    'https://oa-silo.test/sso/session-check/callback',
                ],
                'frontchannel_logout_uris' => [
                    (string) env('SILO_FRONTCHANNEL_LOGOUT_URI', 'https://oa-silo.test/sso/frontchannel-logout'),
                ],
                'scopes' => ['openid', 'email', 'profile', 'ecosystem.read'],
            ],
        ];

        foreach ($definitions as $definition) {
            /** @var OAuthClient|null $existing */
            $existing = OAuthClient::query()
                ->where('slug', $definition['slug'])
                ->orWhere('name', $definition['name'])
                ->first();

            $redirectUris = collect($definition['redirect_uris'])
                ->map(fn (mixed $uri): string => trim((string) $uri))
                ->filter()
                ->unique()
                ->values()
                ->all();

            $frontchannelLogoutUris = collect($definition['frontchannel_logout_uris'])
                ->map(fn (mixed $uri): string => trim((string) $uri))
                ->filter()
                ->unique()
                ->values()
                ->all();

            if ($existing) {
                $existing->forceFill([
                    'slug' => $definition['slug'],
                    'name' => $definition['name'],
                    'base_url' => rtrim((string) $definition['base_url'], '/'),
                    'redirect_uris' => $redirectUris,
                    'frontchannel_logout_uris' => $frontchannelLogoutUris,
                    'grant_types' => ['authorization_code', 'refresh_token'],
                    'scopes' => $definition['scopes'],
                    'is_active' => true,
                    'revoked' => false,
                ])->save();

                continue;
            }

            /** @var OAuthClient $client */
            $client = $clients->createAuthorizationCodeGrantClient(
                name: $definition['name'],
                redirectUris: $redirectUris,
                confidential: true,
            );

            $client->forceFill([
                'slug' => $definition['slug'],
                'base_url' => rtrim((string) $definition['base_url'], '/'),
                'frontchannel_logout_uris' => $frontchannelLogoutUris,
                'scopes' => $definition['scopes'],
                'is_active' => true,
                'revoked' => false,
            ])->save();
        }
    }
}
