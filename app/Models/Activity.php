<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    protected $fillable = [
        'center_id',
        'week',
        'activity',
        'objective',
        'methodology',
        'materials'
    ];
    
    protected $casts = [
        'week' => 'date',
    ];
    
    public function center() : BelongsTo
    {
        return $this->belongsTo(Center::class);
    }
}
