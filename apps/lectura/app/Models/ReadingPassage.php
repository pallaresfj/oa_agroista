<?php

namespace App\Models;

use App\Support\TextWordCounter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReadingPassage extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'difficulty_level',
        'content',
        'word_count',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $passage): void {
            $passage->word_count = TextWordCounter::count($passage->content);
        });
    }

    public function readingAttempts(): HasMany
    {
        return $this->hasMany(ReadingAttempt::class, 'passage_id');
    }
}
