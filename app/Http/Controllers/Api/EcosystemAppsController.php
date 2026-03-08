<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EcosystemApp;
use App\Models\Institution;
use Illuminate\Http\JsonResponse;

class EcosystemAppsController extends Controller
{
    public function index(): JsonResponse
    {
        $institution = Institution::query()->where('is_active', true)->firstOrFail();

        $apps = EcosystemApp::query()
            ->where('institution_id', $institution->id)
            ->where('is_active', true)
            ->with('redirectUris')
            ->orderBy('name')
            ->get()
            ->map(function (EcosystemApp $app): array {
                return [
                    'slug' => $app->slug,
                    'name' => $app->name,
                    'base_url' => $app->base_url,
                    'oauth_client_id' => $app->oauth_client_id,
                    'redirect_uris' => $app->redirectUris->pluck('redirect_uri')->values()->all(),
                    'frontchannel_logout_uris' => $app->redirectUris
                        ->where('is_frontchannel_logout', true)
                        ->pluck('redirect_uri')
                        ->values()
                        ->all(),
                ];
            })
            ->values();

        return response()->json($apps->all());
    }
}
