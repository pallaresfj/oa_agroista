<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'campus_id',
        'day_of_week',
        'check_in_time',
        'check_out_time',
        'tolerance_minutes',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'check_in_time' => 'datetime:H:i',
            'check_out_time' => 'datetime:H:i',
            'tolerance_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Days of week in Spanish.
     */
    public const DAYS = [
        0 => 'Domingo',
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
    ];

    /**
     * Get the user that owns the schedule.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the campus for this schedule.
     */
    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * Get the attendances for this schedule.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Scope a query to only include active schedules.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include schedules for a specific day.
     */
    public function scopeForDay($query, int $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    /**
     * Get the day name in Spanish.
     */
    public function getDayNameAttribute(): string
    {
        return self::DAYS[$this->day_of_week] ?? 'Desconocido';
    }

    /**
     * Get formatted time range.
     */
    public function getTimeRangeAttribute(): string
    {
        $checkIn = $this->check_in_time?->format('H:i') ?? '--:--';
        $checkOut = $this->check_out_time?->format('H:i') ?? '--:--';

        return "{$checkIn} - {$checkOut}";
    }

    /**
     * Get days as options for select.
     */
    public static function daysOptions(): array
    {
        return self::DAYS;
    }
}
