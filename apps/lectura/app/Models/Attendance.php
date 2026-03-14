<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
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
        'schedule_id',
        'date',
        'check_in_time',
        'check_out_time',
        'latitude',
        'longitude',
        'distance_from_campus',
        'status',
        'notes',
        'device_info',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'check_in_time' => 'datetime',
            'check_out_time' => 'datetime',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'distance_from_campus' => 'decimal:2',
            'status' => AttendanceStatus::class,
            'device_info' => 'array',
        ];
    }

    /**
     * Get the user that owns the attendance.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the campus for this attendance.
     */
    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * Get the schedule for this attendance.
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    /**
     * Scope a query to only include attendances for today.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('check_in_time', today());
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeWithStatus($query, AttendanceStatus $status)
    {
        return $query->where('status', $status->value);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('check_in_time', [$startDate, $endDate]);
    }

    /**
     * Scope a query for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query for a specific campus.
     */
    public function scopeForCampus($query, int $campusId)
    {
        return $query->where('campus_id', $campusId);
    }

    /**
     * Get formatted check-in time.
     */
    public function getFormattedCheckInAttribute(): string
    {
        return $this->check_in_time?->format('d/m/Y H:i') ?? '-';
    }

    /**
     * Get formatted check-out time.
     */
    public function getFormattedCheckOutAttribute(): string
    {
        return $this->check_out_time?->format('H:i') ?? '-';
    }

    /**
     * Get formatted coordinates.
     */
    public function getCoordinatesAttribute(): string
    {
        return "{$this->latitude}, {$this->longitude}";
    }

    /**
     * Check if attendance is present (any status except absent).
     */
    public function isPresent(): bool
    {
        return $this->status->isPresent();
    }
}
