<?php

namespace Database\Seeders;

use App\Models\InstitutionSetting;
use Illuminate\Database\Seeder;

class InstitutionSeeder extends Seeder
{
    public function run(): void
    {
        $palette = [
            'primary' => '#1d6362',
            'success' => '#6b9a34',
            'info' => '#99ce93',
            'warning' => '#f8c508',
            'danger' => '#b22222',
        ];

        $this->upsertSetting('name', 'string', 'IED Agropecuaria José María Herrera', null);
        $this->upsertSetting('tagline', 'string', 'Educacion Agropecuaria de Excelencia', null);
        $this->upsertSetting('hero_description', 'string', 'Bienvenido al Portal Unico de Acceso. Gestiona tu informacion en un entorno seguro, moderno y eficiente disenado para nuestra comunidad educativa.', null);
        $this->upsertSetting('location', 'string', 'Pivijay, Magdalena - Colombia', null);
        $this->upsertSetting('name_icon', 'string', 'agriculture', null);
        $this->upsertSetting('nit', 'string', '8190011899', null);
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
