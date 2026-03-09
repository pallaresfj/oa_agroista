<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminsSeeder extends Seeder
{
    public function run(): void
    {
        $emails = array_values(array_filter(array_map(
            static fn (string $item): string => mb_strtolower(trim($item)),
            explode(',', (string) env('SUPERADMIN_EMAILS', '')),
        )));

        foreach ($emails as $email) {
            User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => str($email)->before('@')->replace(['.', '_', '-'], ' ')->title()->toString(),
                    'role' => 'superadmin',
                    'is_active' => true,
                ],
            );
        }
    }
}
