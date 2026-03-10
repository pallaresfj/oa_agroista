<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    public const ROLE_SUPERADMIN = 1;
    public const ROLE_SOPORTE = 2;
    public const ROLE_DIRECTIVO = 3;
    public const ROLE_CENTRO = 4;
    public const ROLE_AREA = 5;
    public const ROLE_DOCENTE = 6;

    /**
     * Legacy numeric role constants mapped to canonical role names.
     *
     * @var array<int, string>
     */
    private const ROLE_ID_TO_NAME = [
        self::ROLE_SUPERADMIN => 'super_admin',
        self::ROLE_SOPORTE => 'Soporte',
        self::ROLE_DIRECTIVO => 'Directivo',
        self::ROLE_CENTRO => 'Centro',
        self::ROLE_AREA => 'Area',
        self::ROLE_DOCENTE => 'Docente',
    ];


    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'auth_subject',
        'password',
        'profile_photo_path',
        'google_avatar_url',
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
            'last_sso_login_at' => 'datetime',
        ];
    }

    public function getFilamentAvatarUrl(): ?string
    {
        $avatarUrl = trim((string) $this->google_avatar_url);

        if (filter_var($avatarUrl, FILTER_VALIDATE_URL)) {
            return $avatarUrl;
        }

        return $this->profile_photo_path
            ? asset('storage/' . $this->profile_photo_path)
            : null;
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->getFilamentAvatarUrl();
    }

    public function centers() : HasMany
    {
        return $this->hasMany(Center::class);
    }

    public function plans() : BelongsToMany
    {
        return $this->belongsToMany(Plan::class);
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class);
    }

    public function hasAnyRoleId(array $ids): bool
    {
        $normalizedIds = collect($ids)
            ->map(static fn (mixed $value): int => (int) $value)
            ->filter(static fn (int $value): bool => $value > 0)
            ->values();

        if ($normalizedIds->isEmpty()) {
            return false;
        }

        $roleNames = $normalizedIds
            ->map(static fn (int $id): ?string => self::ROLE_ID_TO_NAME[$id] ?? null)
            ->filter()
            ->values()
            ->all();

        if ($roleNames !== []) {
            return $this->hasAnyRole($roleNames);
        }

        return $this->roles->whereIn('id', $normalizedIds->all())->isNotEmpty();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() !== 'admin') {
            return false;
        }

        return $this->can('panel_user');
    }
}
