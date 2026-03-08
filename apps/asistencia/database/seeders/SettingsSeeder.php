<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            'institution.name' => 'Mi Institución Educativa',
            'institution.logo_path' => null,
            'attendance.early_check_in_minutes' => '30',
        ];

        foreach ($settings as $key => $value) {
            Setting::setValue($key, $value);
        }

        $this->command->info('Settings seeded successfully!');
    }
}
