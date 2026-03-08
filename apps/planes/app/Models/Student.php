<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Student extends Model
{
    protected $fillable = [
        'center_id',
        'full_name',
        'grade',
        'identification'
    ];
    public function center() : BelongsTo
    {
        return $this->belongsTo(Center::class);
    }
}
