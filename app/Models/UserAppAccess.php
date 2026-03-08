<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAppAccess extends Model
{
    protected $table = 'user_app_access';

    protected $fillable = [
        'user_id',
        'ecosystem_app_id',
        'client_id',
        'is_allowed',
    ];

    protected function casts(): array
    {
        return [
            'is_allowed' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(EcosystemApp::class, 'ecosystem_app_id');
    }
}
