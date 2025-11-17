<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleHistory extends Model
{
    protected $fillable = [
        'employee_id', 'schedule_id', 'effective_from', 'effective_to',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    /** Check if a given date falls within this record's range */
    public function covers($date): bool
    {
        $date = \Carbon\Carbon::parse($date);
        $from = \Carbon\Carbon::parse($this->effective_from);
        $to   = $this->effective_to ? \Carbon\Carbon::parse($this->effective_to) : now();
        return $date->between($from, $to);
    }
}
