<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InstitutionSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EcosystemInstitutionController extends Controller
{
    /**
     * @var array<string, string>
     */
    private const SETTING_TYPES = [
        'name' => 'string',
        'tagline' => 'string',
        'location' => 'string',
        'nit' => 'string',
        'logo_url' => 'string',
        'color_palette' => 'json',
    ];

    /**
     * @return array<string, string>
     */
    private function defaultPalette(): array
    {
        return [
            'primary' => '#f50404',
            'success' => '#00c853',
            'info' => '#0288d1',
            'warning' => '#ff9800',
            'danger' => '#b71c1c',
        ];
    }

    public function show(): JsonResponse
    {
        $settings = InstitutionSetting::query()
            ->whereIn('key', array_keys(self::SETTING_TYPES))
            ->where('is_public', true)
            ->get()
            ->mapWithKeys(function (InstitutionSetting $setting): array {
                $value = $setting->value_json;

                if ($value === null) {
                    $value = $setting->value_text;
                }

                return [$setting->key => $value];
            });

        $palette = $settings->get('color_palette');

        if (! is_array($palette)) {
            $palette = $this->defaultPalette();
        }

        $name = trim((string) $settings->get('name', config('sso.institution_default_name', 'Institucion')));
        $tagline = trim((string) $settings->get('tagline', 'Educacion Agropecuaria de Excelencia'));
        $location = trim((string) $settings->get('location', 'Pivijay, Magdalena - Colombia'));
        $logoUrl = trim((string) $settings->get('logo_url', ''));

        return response()->json([
            'code' => (string) config('sso.institution_code', 'default'),
            'name' => $name,
            'logo_url' => $logoUrl !== '' ? $logoUrl : null,
            'primary_color' => (string) ($palette['primary'] ?? '#f50404'),
            'secondary_color' => (string) ($palette['success'] ?? '#00c853'),
            'settings' => [
                'nit' => trim((string) $settings->get('nit', '')),
                'tagline' => $tagline !== '' ? $tagline : 'Educacion Agropecuaria de Excelencia',
                'location' => $location !== '' ? $location : 'Pivijay, Magdalena - Colombia',
                'color_palette' => $palette,
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user('web') ?? $request->user('api');

        abort_if(! $user || ! $user->isSuperAdmin(), 403, 'Forbidden');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'nit' => ['nullable', 'string', 'max:100'],
            'logo_url' => ['nullable', 'url', 'max:2048'],
            'primary_color' => ['nullable', 'string', 'max:20'],
            'secondary_color' => ['nullable', 'string', 'max:20'],
            'color_palette' => ['nullable', 'array'],
            'color_palette.primary' => ['required_with:color_palette', 'string', 'max:20'],
            'color_palette.success' => ['required_with:color_palette', 'string', 'max:20'],
            'color_palette.info' => ['required_with:color_palette', 'string', 'max:20'],
            'color_palette.warning' => ['required_with:color_palette', 'string', 'max:20'],
            'color_palette.danger' => ['required_with:color_palette', 'string', 'max:20'],
        ]);

        $palette = $this->defaultPalette();

        if (is_array($data['color_palette'] ?? null)) {
            $palette = array_merge($palette, $data['color_palette']);
        }

        if (! empty($data['primary_color'])) {
            $palette['primary'] = (string) $data['primary_color'];
        }

        if (! empty($data['secondary_color'])) {
            $palette['success'] = (string) $data['secondary_color'];
        }

        $this->upsertSetting('name', 'string', (string) $data['name'], null, true);
        $this->upsertSetting('tagline', 'string', (string) ($data['tagline'] ?? ''), null, true);
        $this->upsertSetting('location', 'string', (string) ($data['location'] ?? ''), null, true);
        $this->upsertSetting('nit', 'string', (string) ($data['nit'] ?? ''), null, true);
        $this->upsertSetting('logo_url', 'string', (string) ($data['logo_url'] ?? ''), null, true);
        $this->upsertSetting('color_palette', 'json', null, $palette, true);

        return response()->json([
            'message' => 'Institution updated.',
            'institution' => [
                'code' => (string) config('sso.institution_code', 'default'),
                'name' => (string) $data['name'],
                'logo_url' => $data['logo_url'] ?? null,
                'settings' => [
                    'nit' => (string) ($data['nit'] ?? ''),
                    'tagline' => (string) ($data['tagline'] ?? ''),
                    'location' => (string) ($data['location'] ?? ''),
                    'color_palette' => $palette,
                ],
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $valueJson
     */
    private function upsertSetting(string $key, string $type, ?string $valueText, ?array $valueJson, bool $isPublic): void
    {
        InstitutionSetting::query()->updateOrCreate(
            ['key' => $key],
            [
                'type' => $type,
                'value_text' => $valueText,
                'value_json' => $valueJson,
                'is_public' => $isPublic,
            ]
        );
    }
}
