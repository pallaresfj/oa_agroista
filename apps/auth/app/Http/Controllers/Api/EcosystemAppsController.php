<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OAuthClient;
use Illuminate\Http\JsonResponse;

class EcosystemAppsController extends Controller
{
    public function index(): JsonResponse
    {
        $apps = OAuthClient::query()
            ->where('is_active', true)
            ->where('revoked', false)
            ->orderBy('name')
            ->get()
            ->map(function (OAuthClient $app): array {
                return [
                    'slug' => trim((string) $app->slug) !== '' ? $app->slug : $app->name,
                    'name' => $app->name,
                    'base_url' => $app->base_url,
                    'oauth_client_id' => (string) $app->getKey(),
                    'redirect_uris' => collect($app->redirect_uris ?? [])->map(static fn (mixed $uri): string => trim((string) $uri))->filter()->values()->all(),
                    'frontchannel_logout_uris' => collect($app->frontchannel_logout_uris ?? [])->map(static fn (mixed $uri): string => trim((string) $uri))->filter()->values()->all(),
                ];
            })
            ->values();

        return response()->json($apps->all());
    }
}
