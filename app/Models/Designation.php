<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Designation extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'rate_per_hour'];

    public function employees()
    {
        return $this->hasMany(\App\Models\Employee::class, 'designation_id');
    }

    public function rateHistories()
    {
        return $this->hasMany(\App\Models\DesignationRateHistory::class);
    }

    protected static function booted()
    {
        static::updating(function ($designation) {
            if ($designation->isDirty('rate_per_hour')) {
                // Close the previous active rate
                $designation->rateHistories()
                    ->whereNull('effective_to')
                    ->update(['effective_to' => now()->subDay()]);

                // Add a new active rate record
                $designation->rateHistories()->create([
                    'rate_per_hour' => $designation->rate_per_hour,
                    'effective_from' => now()->toDateString(),
                ]);
            }
        });

        static::created(function ($designation) {
            // Record the initial rate when created
            $designation->rateHistories()->create([
                'rate_per_hour' => $designation->rate_per_hour ?? 0,
                'effective_from' => now()->toDateString(),
            ]);
        });
    }

    public function currentRate()
    {
        return $this->rateHistories()
            ->whereNull('effective_to')
            ->latest('effective_from')
            ->first()?->rate_per_hour ?? $this->rate_per_hour;
    }

    public function rateForDate($date)
    {
        return $this->rateHistories()
            ->whereDate('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')
                  ->orWhereDate('effective_to', '>=', $date);
            })
            ->orderByDesc('effective_from')
            ->value('rate_per_hour') ?? $this->rate_per_hour;
    }
}
