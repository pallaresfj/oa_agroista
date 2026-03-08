<?php

namespace Database\Seeders;

use App\Models\Institution;
use Illuminate\Database\Seeder;

class InstitutionSeeder extends Seeder
{
    public function run(): void
    {
        Institution::query()->updateOrCreate(
            ['code' => (string) env('INSTITUTION_CODE', 'default')],
            [
                'name' => (string) env('INSTITUTION_DEFAULT_NAME', 'Institucion'),
                'logo_url' => (string) env('INSTITUTION_DEFAULT_LOGO_URL', ''),
                'primary_color' => (string) env('INSTITUTION_DEFAULT_PRIMARY_COLOR', '#1d6362'),
                'secondary_color' => (string) env('INSTITUTION_DEFAULT_SECONDARY_COLOR', '#6b9a34'),
                'is_active' => true,
            ]
        );
    }
}
