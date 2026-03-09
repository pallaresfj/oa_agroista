<?php

namespace App\Providers;

use App\Filament\Auth\Responses\LogoutResponse as AppLogoutResponse;
use App\Support\Institution\InstitutionTheme;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(\Filament\Auth\Http\Responses\Contracts\LogoutResponse::class, AppLogoutResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::share('institutionBranding', InstitutionTheme::branding());

        Role::created(function (Role $role): void {
            if (! (bool) config('filament-shield.panel_user.enabled', true)) {
                return;
            }

            $permissionName = trim((string) config('filament-shield.panel_user.name', 'panel_user'));

            if ($permissionName === '') {
                $permissionName = 'panel_user';
            }

            $guardName = trim((string) ($role->guard_name ?: config('auth.defaults.guard', 'web')));

            $permission = Permission::query()->firstOrCreate([
                'name' => $permissionName,
                'guard_name' => $guardName !== '' ? $guardName : 'web',
            ]);

            if (! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
                app(PermissionRegistrar::class)->forgetCachedPermissions();
            }
        });
    }
}
