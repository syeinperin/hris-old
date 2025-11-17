<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DesignationRateHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'designation_id',
        'rate_per_hour',
        'effective_from',
        'effective_to',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to'   => 'date',
    ];

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }
}
