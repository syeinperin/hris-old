<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AuditLog extends Model
{
    use HasFactory;

    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The user who performed the action.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The model that was acted upon (polymorphic relation).
     */
    public function auditable()
    {
        return $this->morphTo();
    }

    /**
     * Get a short, human-readable description of this audit log entry.
     */
    public function getSummaryAttribute()
    {
        $user = $this->user ? $this->user->name : 'System';
        $model = class_basename($this->auditable_type);

        return "{$user} {$this->action} {$model} #{$this->auditable_id}";
    }

    /**
     * Return formatted timestamp for table display.
     */
    public function getFormattedDateAttribute()
    {
        return $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null;
    }

    /**
     * Optionally limit the number of logs kept (to prevent DB bloat).
     */
    public static function pruneOld($days = 90)
    {
        static::where('created_at', '<', now()->subDays($days))->delete();
    }
}
