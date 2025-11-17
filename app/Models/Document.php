<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id','uploaded_by','title','doc_type','file_path','version',
        'notes','status','visibility','expires_at',
    ];

    protected $casts = [
        'expires_at' => 'date',
    ];

    public function employee()  { return $this->belongsTo(Employee::class); }
    public function uploader()  { return $this->belongsTo(User::class, 'uploaded_by'); }

    public function getDownloadUrlAttribute(): ?string
    {
        return $this->file_path ? route('public.files', $this->file_path) : null;
    }

    public function scopeMine($q, int $employeeId)
    {
        return $q->where('employee_id', $employeeId);
    }

    public function scopeVisibleTo($query, $user)
{
    if ($user->hasRole('hr')) {
        return $query->whereNotIn('visibility', ['private_employee']);
    } elseif ($user->hasRole('supervisor')) {
        return $query->whereNotIn('visibility', ['private_employee', 'employee']);
    } elseif ($user->employee) {
        return $query->where('employee_id', $user->employee->id)
                     ->whereIn('visibility', ['employee', 'private_employee']);
    }

    return $query->whereNull('id'); // deny all others
}

    public function isExpired(): bool
    {
        return $this->expires_at ? now()->greaterThan($this->expires_at) : false;
    }
}
