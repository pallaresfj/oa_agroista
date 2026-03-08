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
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_SOPORTE = 'soporte';

    public const ROLE_DIRECTIVO = 'directivo';

    public const ROLE_DOCENTE = 'docente';

    protected string $guard_name = 'web';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'auth_subject',
        'institution_code',
        'password',
        'phone',
        'identification_number',
        'is_active',
        'google_avatar_url',
        'avatar_path',
        'last_sso_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_sso_login_at' => 'datetime',
        ];
    }

    /**
     * Get the schedules for this user.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * Get the attendances for this user.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Check if user can access Filament admin panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->hasRole(self::ROLE_SUPER_ADMIN)) {
            return true;
        }

        if ($this->can('panel_user')) {
            return true;
        }

        return false;
    }

    /**
     * Check if user is soporte.
     */
    public function isSoporte(): bool
    {
        return $this->hasAnyRole([self::ROLE_SUPER_ADMIN, self::ROLE_SOPORTE]);
    }

    /**
     * Check if user is directivo.
     */
    public function isDirectivo(): bool
    {
        return $this->hasAnyRole([self::ROLE_SUPER_ADMIN, self::ROLE_DIRECTIVO]);
    }

    /**
     * Check if user is docente.
     */
    public function isDocente(): bool
    {
        return $this->hasRole(self::ROLE_DOCENTE);
    }

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include users with a specific role.
     */
    public function scopeWithRole(Builder $query, UserRole $role): Builder
    {
        return $query->whereHas('roles', fn (Builder $relatedQuery) => $relatedQuery->where('name', $role->value));
    }

    /**
     * Scope a query to only include docentes.
     */
    public function scopeDocentes(Builder $query): Builder
    {
        return $query->withRole(UserRole::DOCENTE);
    }

    /**
     * Get formatted role label.
     */
    public function getRoleLabelAttribute(): string
    {
        if ($this->isSoporte()) {
            return UserRole::SOPORTE->label();
        }

        if ($this->isDirectivo()) {
            return UserRole::DIRECTIVO->label();
        }

        if ($this->isDocente()) {
            return UserRole::DOCENTE->label();
        }

        return 'Sin rol';
    }

    public function dashboardRouteName(): string
    {
        if ($this->isDirectivo()) {
            return 'directivo.dashboard';
        }

        if ($this->isDocente()) {
            return 'docente.dashboard';
        }

        return 'dashboard';
    }

    public function ensureApplicationRole(string $preferredRole = self::ROLE_DOCENTE): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('model_has_roles') || $this->roles()->exists()) {
            return;
        }

        $normalizedRole = mb_strtolower(trim($preferredRole));
        $roleName = in_array($normalizedRole, [self::ROLE_SOPORTE, self::ROLE_DIRECTIVO, self::ROLE_DOCENTE], true)
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
