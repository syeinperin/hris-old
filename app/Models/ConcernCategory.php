<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConcernCategory extends Model
{
    protected $fillable = ['name'];

    public function concerns()
    {
        return $this->hasMany(Concern::class);
    }
}
