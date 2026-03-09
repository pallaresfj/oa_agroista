<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_delete_user_blocks_self_deletion(): void
    {
        $user = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@iedagropivijay.edu.co',
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $this->actingAs($user, 'web');

        $this->expectException(ValidationException::class);

        UserResource::deleteUser($user);
    }

    public function test_delete_user_blocks_last_active_superadmin(): void
    {
        $user = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@iedagropivijay.edu.co',
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $operator = User::query()->create([
            'name' => 'Operador',
            'email' => 'operador@iedagropivijay.edu.co',
            'role' => 'user',
            'is_active' => true,
        ]);

        $this->actingAs($operator, 'web');

        $this->expectException(ValidationException::class);

        UserResource::deleteUser($user);
    }

    public function test_delete_user_allows_regular_user_removal(): void
    {
        $superadmin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@iedagropivijay.edu.co',
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $user = User::query()->create([
            'name' => 'Docente',
            'email' => 'docente@iedagropivijay.edu.co',
            'role' => 'user',
            'is_active' => true,
        ]);

        $this->actingAs($superadmin, 'web');

        $deleted = UserResource::deleteUser($user);

        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_user_role_controls_panel_access(): void
    {
        $superadmin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@iedagropivijay.edu.co',
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $user = User::query()->create([
            'name' => 'Docente',
            'email' => 'docente@iedagropivijay.edu.co',
            'role' => 'user',
            'is_active' => true,
        ]);

        $panel = \Filament\Facades\Filament::getPanel('admin');

        $this->assertTrue($superadmin->canAccessPanel($panel));
        $this->assertFalse($user->canAccessPanel($panel));
    }
}
