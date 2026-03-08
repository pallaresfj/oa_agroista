<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rubric extends Model
{
    protected $fillable = [
        'subject_id',
        'period',
        'criterion',
        'superior_level',
        'high_level',
        'basic_level',
        'low_level'
    ];
    public function subject() : BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
