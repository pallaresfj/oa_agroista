<?php

namespace App\Models;

use App\Enums\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

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
        'role',
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
            'role' => UserRole::class,
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
        return $this->role?->canAccessAdmin() && $this->is_active;
    }

    /**
     * Check if user is soporte.
     */
    public function isSoporte(): bool
    {
        return $this->role === UserRole::SOPORTE;
    }

    /**
     * Check if user is directivo.
     */
    public function isDirectivo(): bool
    {
        return $this->role === UserRole::DIRECTIVO;
    }

    /**
     * Check if user is docente.
     */
    public function isDocente(): bool
    {
        return $this->role === UserRole::DOCENTE;
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
    public function scopeWithRole($query, UserRole $role)
    {
        return $query->where('role', $role->value);
    }

    /**
     * Scope a query to only include docentes.
     */
    public function scopeDocentes($query)
    {
        return $query->where('role', UserRole::DOCENTE->value);
    }

    /**
     * Get formatted role label.
     */
    public function getRoleLabelAttribute(): string
    {
        return $this->role?->label() ?? 'Sin rol';
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
