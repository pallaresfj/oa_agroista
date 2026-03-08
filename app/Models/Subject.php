<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Subject extends Model
{
    protected $fillable = [
        'name',
        'plan_id',
        'grade',
        'weekly_hours',
        'interest_centers',
        'contributions',
        'strategies',
    ];
    
    protected $casts = [
        'interest_centers' => 'array',
    ];
    public function plan() : BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
    public function topics() : HasMany
    {
        return $this->hasMany(Topic::class);
    }
    public function rubrics() : HasMany
    {
        return $this->hasMany(Rubric::class);
    }
}
