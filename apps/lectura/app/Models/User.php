<?php

namespace App\Models;

use App\Enums\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    use HasFactory, HasRoles, Notifiable;

    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_DOCENTE = 'docente';

    public const ROLE_ESTUDIANTE = 'estudiante';

    protected string $guard_name = 'web';

    protected $fillable = [
        'name',
        'email',
        'auth_subject',
        'password',
        'phone',
        'identification_number',
        'is_active',
        'google_avatar_url',
        'avatar_path',
        'last_sso_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_sso_login_at' => 'datetime',
        ];
    }

    public function readingAttempts(): HasMany
    {
        return $this->hasMany(ReadingAttempt::class, 'teacher_id');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->isDocente()) {
            return true;
        }

        if ($this->can('panel_user')) {
            return true;
        }

        return false;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(self::ROLE_SUPER_ADMIN);
    }

    public function isDocente(): bool
    {
        return $this->hasAnyRole([self::ROLE_SUPER_ADMIN, self::ROLE_DOCENTE]);
    }

    public function isEstudiante(): bool
    {
        return $this->hasRole(self::ROLE_ESTUDIANTE);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeWithRole(Builder $query, UserRole $role): Builder
    {
        return $query->whereHas('roles', fn (Builder $relatedQuery) => $relatedQuery->where('name', $role->value));
    }

    public function scopeDocentes(Builder $query): Builder
    {
        return $query->withRole(UserRole::DOCENTE);
    }

    public function getRoleLabelAttribute(): string
    {
        if ($this->isSuperAdmin()) {
            return UserRole::SUPER_ADMIN->label();
        }

        if ($this->isDocente()) {
            return UserRole::DOCENTE->label();
        }

        if ($this->isEstudiante()) {
            return UserRole::ESTUDIANTE->label();
        }

        return 'Sin rol asignado';
    }

    public function dashboardRouteName(): string
    {
        if ($this->isDocente() || $this->isSuperAdmin()) {
            return 'dashboard';
        }

        return 'login';
    }

    public function ensureApplicationRole(string $preferredRole = self::ROLE_DOCENTE): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('model_has_roles') || $this->roles()->exists()) {
            return;
        }

        $normalizedRole = mb_strtolower(trim($preferredRole));
        $roleName = in_array($normalizedRole, [self::ROLE_SUPER_ADMIN, self::ROLE_DOCENTE, self::ROLE_ESTUDIANTE], true)
            ? $normalizedRole
            : self::ROLE_DOCENTE;

        $this->assignRole($roleName);
    }

    public function getFilamentAvatarUrl(): ?string
    {
        $googleAvatarUrl = trim((string) $this->google_avatar_url);

        if ($googleAvatarUrl !== '' && filter_var($googleAvatarUrl, FILTER_VALIDATE_URL)) {
            return $googleAvatarUrl;
        }

        return null;
    }
}
