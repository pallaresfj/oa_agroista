<?php

namespace App\Models;

use App\Enums\ReadingErrorType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingError extends Model
{
    use HasFactory;

    protected $fillable = [
        'attempt_id',
        'error_type',
        'occurred_at_seconds',
        'word_index',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'error_type' => ReadingErrorType::class,
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ReadingAttempt::class, 'attempt_id');
    }
}
