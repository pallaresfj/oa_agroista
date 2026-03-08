<?php

namespace Database\Seeders;

use App\Models\EcosystemApp;
use App\Models\Institution;
use App\Models\OAuthClient;
use Illuminate\Database\Seeder;

class EcosystemAppsSeeder extends Seeder
{
    public function run(): void
    {
        $institution = Institution::query()->where('is_active', true)->first();

        if (! $institution) {
            return;
        }

        $definitions = [
            'planes' => [
                'name' => 'Planes',
                'base_url' => (string) env('PLANES_BASE_URL', 'https://planes.institucionx.edu.co'),
            ],
            'asistencia' => [
                'name' => 'Asistencia',
                'base_url' => (string) env('ASISTENCIA_BASE_URL', 'https://asistencia.institucionx.edu.co'),
            ],
            'silo' => [
                'name' => 'Silo',
                'base_url' => (string) env('SILO_BASE_URL', 'https://silo.institucionx.edu.co'),
            ],
        ];

        foreach ($definitions as $slug => $definition) {
            $oauthClient = OAuthClient::query()->where('name', $slug)->first();

            /** @var EcosystemApp $app */
            $app = EcosystemApp::query()->updateOrCreate(
                [
                    'institution_id' => $institution->id,
                    'slug' => $slug,
                ],
                [
                    'name' => $definition['name'],
                    'base_url' => rtrim($definition['base_url'], '/'),
                    'oauth_client_id' => $oauthClient?->getKey(),
                    'is_active' => true,
                ]
            );

            if (! $oauthClient || empty($oauthClient->redirect_uris)) {
                continue;
            }

            $redirectUris = collect($oauthClient->redirect_uris)
                ->map(fn (mixed $uri): string => trim((string) $uri))
                ->filter()
                ->unique()
                ->values();

            $app->redirectUris()->delete();

            foreach ($redirectUris as $uri) {
                $isFrontchannel = str_contains($uri, '/sso/frontchannel-logout');

                $app->redirectUris()->create([
                    'redirect_uri' => $uri,
                    'is_frontchannel_logout' => $isFrontchannel,
                ]);
            }
        }
    }
}
