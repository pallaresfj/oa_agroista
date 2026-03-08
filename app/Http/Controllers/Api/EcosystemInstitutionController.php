<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EcosystemInstitutionController extends Controller
{
    public function show(): JsonResponse
    {
        $institution = Institution::query()
            ->where('is_active', true)
            ->with(['settings' => fn ($query) => $query->where('is_public', true)])
            ->firstOrFail();

        $settings = $institution->settings->mapWithKeys(function ($setting): array {
            $value = $setting->value_json;

            if ($value === null) {
                $value = $setting->value_text;
            }

            return [$setting->key => $value];
        });

        return response()->json([
            'code' => $institution->code,
            'name' => $institution->name,
            'logo_url' => $institution->logo_url,
            'primary_color' => $institution->primary_color,
            'secondary_color' => $institution->secondary_color,
            'settings' => $settings,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user('web') ?? $request->user('api');

        abort_if(! $user || ! $user->isSuperAdmin(), 403, 'Forbidden');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'logo_url' => ['nullable', 'url', 'max:2048'],
            'primary_color' => ['nullable', 'string', 'max:20'],
            'secondary_color' => ['nullable', 'string', 'max:20'],
        ]);

        $institution = Institution::query()->where('is_active', true)->firstOrFail();
        $institution->fill($data);
        $institution->save();

        return response()->json([
            'message' => 'Institution updated.',
            'institution' => $institution,
        ]);
    }
}
