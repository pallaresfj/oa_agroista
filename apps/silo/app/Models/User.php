<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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

    public const ROLE_ADMINISTRATIVO = 'administrativo';

    public const ROLE_VISITANTE = 'visitante';

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
        'google_subject',
        'password',
        'avatar_url',
        'google_avatar_url',
        'last_google_login_at',
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
            'last_google_login_at' => 'datetime',
            'last_sso_login_at' => 'datetime',
        ];
    }

    /**
     * Determine if the user can access the Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() !== 'admin') {
            return false;
        }

        if ($this->hasRole(self::ROLE_SUPER_ADMIN)) {
            return true;
        }

        return $this->can('panel_user');
    }

    /**
     * Ensure at least one app role is assigned.
     */
    public function ensureApplicationRole(string $preferredRole = self::ROLE_DOCENTE): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('model_has_roles') || $this->roles()->exists()) {
            return;
        }

        $roleName = match (mb_strtolower(trim($preferredRole))) {
            self::ROLE_SUPER_ADMIN => self::ROLE_SUPER_ADMIN,
            self::ROLE_SOPORTE, 'soporte' => self::ROLE_SOPORTE,
            self::ROLE_DIRECTIVO, 'rector', 'editor' => self::ROLE_DIRECTIVO,
            self::ROLE_ADMINISTRATIVO, 'administrador' => self::ROLE_ADMINISTRATIVO,
            self::ROLE_VISITANTE, 'visitante' => self::ROLE_VISITANTE,
            default => self::ROLE_DOCENTE,
        };

        $this->assignRole($roleName);
    }

    /**
     * @return list<string>
     */
    public static function applicationRoles(): array
    {
        return [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_SOPORTE,
            self::ROLE_DIRECTIVO,
            self::ROLE_DOCENTE,
            self::ROLE_ADMINISTRATIVO,
            self::ROLE_VISITANTE,
        ];
    }

    /**
     * Get the avatar URL for Filament sidebar.
     */
    public function getFilamentAvatarUrl(): ?string
    {
        $googleAvatarUrl = trim((string) $this->google_avatar_url);

        if ($googleAvatarUrl === '' || ! filter_var($googleAvatarUrl, FILTER_VALIDATE_URL)) {
            return null;
        }

        return $googleAvatarUrl;
    }
}
