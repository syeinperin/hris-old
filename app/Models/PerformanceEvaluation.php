<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerformanceEvaluation extends Model
{
    protected $fillable = [
        'employee_id',
        'evaluator_id',
        'period_start',
        'period_end',
        'type',
        'overall_score',
        'remarks',
        'status',
        'promotion_recommended',
        'regularization_recommended'
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end'   => 'date',
        'overall_score'=> 'decimal:2',
        'promotion_recommended' => 'boolean',
        'regularization_recommended' => 'boolean',
    ];

    public function employee()  { return $this->belongsTo(Employee::class); }
    public function evaluator() { return $this->belongsTo(User::class, 'evaluator_id'); }
    public function scores()    { return $this->hasMany(PerformanceScore::class, 'evaluation_id'); }

    /** ✅ Computed result based on 5-point Likert ranges */
    public function getResultLabelAttribute(): string
    {
        $score = (float) $this->overall_score;
        return match (true) {
            $score >= 90 => 'Excellent',
            $score >= 80 => 'Very Satisfactory',
            $score >= 70 => 'Satisfactory',
            $score >= 60 => 'Needs Improvement',
            default      => 'Poor',
        };
    }

    /** ✅ Corresponding badge color */
    public function getResultColorAttribute(): string
    {
        return match ($this->result_label) {
            'Excellent'          => 'success',
            'Very Satisfactory'  => 'primary',
            'Satisfactory'       => 'info',
            'Needs Improvement'  => 'warning',
            'Poor'               => 'danger',
            default              => 'secondary',
        };
    }

    /** ✅ Auto-recommend promotion / regularization */
    protected static function booted()
    {
        static::saved(function ($eval) {
            if ($eval->type === 'probationary' && $eval->overall_score >= 80) {
                $eval->updateQuietly(['regularization_recommended' => true]);
            }

            if (in_array($eval->type, ['regular', 'kpi', '360']) && $eval->overall_score >= 85) {
                $eval->updateQuietly(['promotion_recommended' => true]);
            }
        });
    }
}
