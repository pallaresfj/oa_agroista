<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Center extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'objective',
        'academic_year',
        'image_path',
    ];
    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function teachers() : HasMany
    {
        return $this->hasMany(Teacher::class);
    }
    public function students() : HasMany
    {
        return $this->hasMany(Student::class);
    }
    public function activities() : HasMany
    {
        return $this->hasMany(Activity::class);
    }
    public function budgets() : HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function getImageUrlAttribute(): string
    {
        $imagePath = trim((string) $this->image_path);

        if ($imagePath === '') {
            return asset('images/centros.jpg');
        }

        if (Storage::disk('public')->exists($imagePath)) {
            return asset('storage/'.$imagePath);
        }

        return asset('images/centros.jpg');
    }
}
