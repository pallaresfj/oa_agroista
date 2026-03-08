<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Plan extends Model
{
    protected $fillable = [
        'year',
        'name',
        'cover',
        'school_profile_id',
        'justification',
        'objectives',
        'methodology'
    ];
    public function schoolProfile() : BelongsTo
    {
        return $this->belongsTo(SchoolProfile::class);
    }
    public function subjects() : HasMany
    {
        return $this->hasMany(Subject::class);
    }
    public function users() : BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function getCoverUrlAttribute(): string
    {
        $cover = trim((string) $this->cover);

        if ($cover === '') {
            return asset('images/planes.jpg');
        }

        if (Storage::disk('public')->exists($cover)) {
            return asset('storage/'.$cover);
        }

        return asset('images/planes.jpg');
    }
}
