<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'student_code',
        'grade',
        'section',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function readingAttempts(): HasMany
    {
        return $this->hasMany(ReadingAttempt::class);
    }

    public function getFullGroupAttribute(): string
    {
        return collect([$this->grade, $this->section])
            ->filter()
            ->implode(' ');
    }
}
