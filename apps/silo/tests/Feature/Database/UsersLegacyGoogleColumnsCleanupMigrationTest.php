<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('backfills auth_subject and last_sso_login_at before dropping legacy google columns', function () {
    if (! Schema::hasColumn('users', 'google_subject')) {
        Schema::table('users', function ($table): void {
            $table->string('google_subject')->nullable()->after('email');
        });
    }

    if (! Schema::hasColumn('users', 'last_google_login_at')) {
        Schema::table('users', function ($table): void {
            $table->timestamp('last_google_login_at')->nullable()->after('email_verified_at');
        });
    }

    if (! Schema::hasColumn('users', 'avatar_url')) {
        Schema::table('users', function ($table): void {
            $table->string('avatar_url')->nullable()->after('password');
        });
    }

    $now = now();
    $legacyLogin = $now->copy()->subMinute();

    DB::table('users')->insert([
        'name' => 'Docente Legacy',
        'email' => 'legacy-docente@example.com',
        'password' => bcrypt('password'),
        'auth_subject' => null,
        'google_subject' => 'legacy-subject-1',
        'last_sso_login_at' => null,
        'last_google_login_at' => $legacyLogin,
        'avatar_url' => 'avatars/legacy.png',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $migration = require database_path('migrations/2026_03_09_100000_backfill_and_drop_legacy_google_columns_from_users_table.php');
    $migration->up();

    $user = DB::table('users')->where('email', 'legacy-docente@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user?->auth_subject)->toBe('legacy-subject-1');
    expect($user?->last_sso_login_at)->not->toBeNull();

    expect(Schema::hasColumn('users', 'google_subject'))->toBeFalse();
    expect(Schema::hasColumn('users', 'last_google_login_at'))->toBeFalse();
    expect(Schema::hasColumn('users', 'avatar_url'))->toBeFalse();
});
