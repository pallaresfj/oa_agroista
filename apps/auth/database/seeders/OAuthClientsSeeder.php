<?php

namespace Database\Seeders;

use App\Models\OAuthClient;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OAuthClientsSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            [
                'slug' => 'planes',
                'name' => 'Planes',
                'client_id' => (string) env('PLANES_CLIENT_ID', '019ccb79-60df-7038-8f27-4e6dd4a68c60'),
                'client_secret' => (string) env('PLANES_CLIENT_SECRET', 'planes-local-secret'),
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
                'client_id' => (string) env('ASISTENCIA_CLIENT_ID', '019ccb79-61b5-7018-abdb-4ae22d9922a1'),
                'client_secret' => (string) env('ASISTENCIA_CLIENT_SECRET', 'asistencia-local-secret'),
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
                'client_id' => (string) env('SILO_CLIENT_ID', '019ccb79-6289-724c-9fdf-fb486d4a3545'),
                'client_secret' => (string) env('SILO_CLIENT_SECRET', 'silo-local-secret'),
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
            /** @var OAuthClient|null $client */
            $client = OAuthClient::query()
                ->where('slug', $definition['slug'])
                ->orWhere('name', $definition['name'])
                ->first();

            $configuredId = trim((string) ($definition['client_id'] ?? ''));
            $configuredSecret = trim((string) ($definition['client_secret'] ?? ''));

            if ($configuredId !== '' && $client && (string) $client->getKey() !== $configuredId) {
                $client->delete();
                $client = null;
            }

            if (! $client && $configuredId !== '') {
                $client = OAuthClient::query()->find($configuredId);
            }

            if (! $client) {
                $client = new OAuthClient();
                $client->id = $configuredId !== '' ? $configuredId : (string) Str::uuid();
            }

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

            $client->forceFill([
                'name' => $definition['name'],
                'slug' => $definition['slug'],
                'base_url' => rtrim((string) $definition['base_url'], '/'),
                'secret' => $configuredSecret !== '' ? $configuredSecret : ((string) $client->secret !== '' ? $client->secret : Str::random(40)),
                'provider' => null,
                'redirect_uris' => $redirectUris,
                'frontchannel_logout_uris' => $frontchannelLogoutUris,
                'grant_types' => ['authorization_code', 'refresh_token'],
                'scopes' => $definition['scopes'],
                'is_active' => true,
                'revoked' => false,
            ])->save();
        }
    }
}
