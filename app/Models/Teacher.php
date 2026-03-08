<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Teacher extends Model
{
    protected $fillable = [
        'center_id',
        'full_name',
        'identification',
        'email',
        'phone',
        'profile_photo_path',
    ];
    public function center() : BelongsTo
    {
        return $this->belongsTo(Center::class);
    }
}
