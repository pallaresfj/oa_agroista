<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Oidc\UserInfoController;
use App\Models\OAuthClient;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::get('/', function () {
    $user = auth()->user();

    if ($user instanceof \App\Models\User && $user->is_active && $user->isSuperAdmin()) {
        return redirect('/admin');
    }

    $iconBySlug = [
        'planes' => 'school',
        'asistencia' => 'assignment_ind',
        'silo' => 'folder_managed',
    ];

    $descriptionBySlug = [
        'planes' => 'Plataforma integral de planeacion estrategica y seguimiento escolar.',
        'asistencia' => 'Sistema de control de asistencia docente y registro de actividades diarias.',
        'silo' => 'Sistema de gestion documental para la administracion eficiente de archivos.',
    ];

    $ecosystemApps = OAuthClient::query()
        ->where('is_active', true)
        ->where('revoked', false)
        ->orderBy('name')
        ->get()
        ->map(function (OAuthClient $client) use ($iconBySlug, $descriptionBySlug): ?array {
            $slug = Str::lower(trim((string) ($client->slug ?: $client->name)));

            $url = rtrim(trim((string) $client->base_url), '/');

            if ($url === '') {
                $firstRedirectUri = collect($client->redirect_uris ?? [])
                    ->map(static fn (mixed $uri): string => trim((string) $uri))
                    ->first(static fn (string $uri): bool => $uri !== '');

                if (is_string($firstRedirectUri) && $firstRedirectUri !== '') {
                    $scheme = parse_url($firstRedirectUri, PHP_URL_SCHEME);
                    $host = parse_url($firstRedirectUri, PHP_URL_HOST);

                    if (is_string($scheme) && is_string($host) && $scheme !== '' && $host !== '') {
                        $url = sprintf('%s://%s', $scheme, $host);
                    }
                }
            }

            if ($url === '') {
                return null;
            }

            $host = parse_url($url, PHP_URL_HOST);

            return [
                'name' => trim((string) $client->name) !== '' ? $client->name : Str::headline($slug),
                'description' => $descriptionBySlug[$slug] ?? 'Aplicacion institucional integrada al ecosistema de autenticacion.',
                'url' => $url,
                'host' => is_string($host) && $host !== '' ? $host : preg_replace('#^https?://#', '', $url),
                'icon' => $iconBySlug[$slug] ?? 'apps',
            ];
        })
        ->filter()
        ->values();

    return response()->view('welcome', [
        'ecosystemApps' => $ecosystemApps,
    ], 200);
})->name('home');

Route::get('/login', [GoogleAuthController::class, 'login'])->name('login');
Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirectToGoogle'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
Route::get('/auth/google/session-check/start', [GoogleAuthController::class, 'startSessionCheck'])
    ->middleware('auth')
    ->name('auth.google.session-check.start');
Route::get('/auth/google/session-check/callback', [GoogleAuthController::class, 'completeSessionCheck'])
    ->name('auth.google.session-check.callback');
Route::match(['GET', 'POST'], '/logout', [GoogleAuthController::class, 'logout'])->name('logout');

Route::get('/oauth/userinfo', UserInfoController::class)
    ->middleware(['auth:api', 'openid.scope'])
    ->name('openid.userinfo');
