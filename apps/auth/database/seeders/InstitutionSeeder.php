<?php

namespace Database\Seeders;

use App\Models\InstitutionSetting;
use Illuminate\Database\Seeder;

class InstitutionSeeder extends Seeder
{
    public function run(): void
    {
        $palette = [
            'primary' => (string) env('INSTITUTION_DEFAULT_PRIMARY_COLOR', '#f50404'),
            'success' => (string) env('INSTITUTION_DEFAULT_SUCCESS_COLOR', '#00c853'),
            'info' => (string) env('INSTITUTION_DEFAULT_INFO_COLOR', '#0288d1'),
            'warning' => (string) env('INSTITUTION_DEFAULT_WARNING_COLOR', '#ff9800'),
            'danger' => (string) env('INSTITUTION_DEFAULT_DANGER_COLOR', '#b71c1c'),
        ];

        $this->upsertSetting('name', 'string', (string) env('INSTITUTION_DEFAULT_NAME', 'Institucion'), null);
        $this->upsertSetting('nit', 'string', (string) env('INSTITUTION_DEFAULT_NIT', ''), null);
        $this->upsertSetting('logo_url', 'string', (string) env('INSTITUTION_DEFAULT_LOGO_URL', ''), null);
        $this->upsertSetting('color_palette', 'json', null, $palette);
    }

    /**
     * @param  array<string, string>|null  $valueJson
     */
    private function upsertSetting(string $key, string $type, ?string $valueText, ?array $valueJson): void
    {
        InstitutionSetting::query()->updateOrCreate(
            ['key' => $key],
            [
                'type' => $type,
                'value_text' => $valueText,
                'value_json' => $valueJson,
                'is_public' => true,
            ],
        );
    }
}
