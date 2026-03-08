<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    protected $fillable = [
        'center_id',
        'item',
        'quantity',
        'unit_value',
        'observations'
    ];
    public function center() : BelongsTo
    {
        return $this->belongsTo(Center::class);
    }
}
