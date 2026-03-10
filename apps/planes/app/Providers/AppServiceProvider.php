<?php

namespace App\Providers;

use App\Filament\Auth\Responses\LogoutResponse as AppLogoutResponse;
use App\Models\User;
use App\Support\Institution\InstitutionTheme;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
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

        Gate::before(function (mixed $user): ?bool {
            if (! $user instanceof User) {
                return null;
            }

            if ($user->hasAnyRole(['super_admin', 'Soporte'])) {
                return true;
            }

            $email = Str::lower(trim((string) $user->email));

            if ($email === '') {
                return null;
            }

            $supportEmails = collect(config('sso.support_emails', []))
                ->map(static fn (mixed $item): string => Str::lower(trim((string) $item)))
                ->filter();

            return $supportEmails->contains($email) ? true : null;
        });

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
