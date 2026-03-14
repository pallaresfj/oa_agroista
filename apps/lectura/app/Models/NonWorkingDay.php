<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NonWorkingDay extends Model
{
    protected $fillable = [
        'date',
        'name',
        'type',
        'campus_id',
        'is_recurring',
        'description',
    ];

    protected $casts = [
        'date' => 'date',
        'is_recurring' => 'boolean',
    ];

    /**
     * Tipos de días no laborables.
     */
    public const TYPE_HOLIDAY = 'holiday';

    public const TYPE_VACATION = 'vacation';

    public const TYPE_SPECIAL = 'special';

    /**
     * Obtener las etiquetas para los tipos.
     */
    public static function getTypeLabels(): array
    {
        return [
            self::TYPE_HOLIDAY => 'Festivo',
            self::TYPE_VACATION => 'Vacaciones',
            self::TYPE_SPECIAL => 'Día especial',
        ];
    }

    /**
     * Relación con Campus (opcional).
     */
    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * Verificar si una fecha es día no laborable para un campus específico.
     */
    public static function isNonWorkingDay(Carbon|string $date, ?int $campusId = null): bool
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);

        $query = static::query()
            // Filtrar por campus: aplica si campus_id es null (todas las sedes) o coincide con el campus
            ->where(function ($q) use ($campusId) {
                $q->whereNull('campus_id');
                if ($campusId) {
                    $q->orWhere('campus_id', $campusId);
                }
            })
            // Filtrar por fecha (exacta o recurrente)
            ->where(function ($q) use ($date) {
                $q->where(function ($subQ) use ($date) {
                    // Fecha exacta no recurrente - usar whereDate para compatibilidad SQLite/MySQL
                    $subQ->whereDate('date', $date->toDateString())
                        ->where('is_recurring', false);
                })
                    ->orWhere(function ($subQ) use ($date) {
                        // Fechas recurrentes (mismo mes y día, cualquier año)
                        $subQ->whereMonth('date', $date->month)
                            ->whereDay('date', $date->day)
                            ->where('is_recurring', true);
                    });
            });

        return $query->exists();
    }

    /**
     * Obtener días no laborables en un rango de fechas.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getInRange(Carbon $startDate, Carbon $endDate, ?int $campusId = null)
    {
        $query = static::query()
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('date', [$startDate, $endDate])
                    ->where('is_recurring', false);
            })
            ->orWhere('is_recurring', true);

        $query->where(function ($q) use ($campusId) {
            $q->whereNull('campus_id');
            if ($campusId) {
                $q->orWhere('campus_id', $campusId);
            }
        });

        return $query->get();
    }

    /**
     * Scope para filtrar por tipo.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope para días recurrentes.
     */
    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    /**
     * Obtener la etiqueta del tipo.
     */
    public function getTypeLabelAttribute(): string
    {
        return self::getTypeLabels()[$this->type] ?? $this->type;
    }
}
