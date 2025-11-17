<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Attendance extends Model
{
    protected $fillable = [
        'employee_id',
        'schedule_id',
        'time_in',
        'time_out',
        'is_manual',
    ];

    protected $casts = [
        'time_in'   => 'datetime',
        'time_out'  => 'datetime',
        'is_manual' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /** Optional helper to group by date */
    public function getDayAttribute(): ?string
    {
        return $this->time_in?->toDateString();
    }

    /** Filter by time_in date range */
    public function scopeBetween(Builder $q, string $from, string $to): Builder
    {
        return $q->whereDate('time_in', '>=', $from)
                 ->whereDate('time_in', '<=', $to);
    }
}
