<?php

namespace App\Models;

use App\Enums\ReadingErrorType;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReadingAttempt extends Model
{
    use HasFactory;

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'student_id',
        'teacher_id',
        'passage_id',
        'status',
        'started_at',
        'finished_at',
        'duration_seconds',
        'word_count',
        'words_per_minute',
        'total_errors',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'words_per_minute' => 'decimal:2',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function passage(): BelongsTo
    {
        return $this->belongsTo(ReadingPassage::class, 'passage_id');
    }

    public function errors(): HasMany
    {
        return $this->hasMany(ReadingError::class, 'attempt_id');
    }

    public function registerError(
        ReadingErrorType $type,
        int $occurredAtSeconds,
        ?int $wordIndex = null,
        ?string $comment = null,
    ): ReadingError {
        $error = $this->errors()->create([
            'error_type' => $type,
            'occurred_at_seconds' => max(0, $occurredAtSeconds),
            'word_index' => $wordIndex,
            'comment' => $comment,
        ]);

        $this->forceFill([
            'total_errors' => $this->errors()->count(),
        ])->save();

        return $error;
    }

    public function complete(?CarbonInterface $finishedAt = null, ?string $notes = null): void
    {
        $finishedAt ??= now();
        $durationSeconds = max(1, (int) $this->started_at?->diffInSeconds($finishedAt));
        $wordCount = $this->word_count ?: (int) $this->passage?->word_count;
        $wordsPerMinute = round(($wordCount / $durationSeconds) * 60, 2);

        $this->forceFill([
            'status' => self::STATUS_COMPLETED,
            'finished_at' => $finishedAt,
            'duration_seconds' => $durationSeconds,
            'word_count' => $wordCount,
            'words_per_minute' => $wordsPerMinute,
            'total_errors' => $this->errors()->count(),
            'notes' => $notes ?: $this->notes,
        ])->save();
    }

    public function cancel(?string $notes = null): void
    {
        $this->forceFill([
            'status' => self::STATUS_CANCELLED,
            'finished_at' => now(),
            'duration_seconds' => $this->started_at ? max(0, (int) $this->started_at->diffInSeconds(now())) : 0,
            'notes' => $notes ?: $this->notes,
        ])->save();
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }
}
