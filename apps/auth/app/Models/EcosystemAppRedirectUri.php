<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EcosystemAppRedirectUri extends Model
{
    protected $fillable = [
        'ecosystem_app_id',
        'redirect_uri',
        'is_frontchannel_logout',
    ];

    protected function casts(): array
    {
        return [
            'is_frontchannel_logout' => 'boolean',
        ];
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(EcosystemApp::class, 'ecosystem_app_id');
    }
}
