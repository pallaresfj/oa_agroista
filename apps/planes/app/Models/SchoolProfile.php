<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolProfile extends Model
{
    protected $fillable = ['mission', 'vision'];
    public function plans() : HasMany
    {
        return $this->hasMany(Plan::class);
    }
}
