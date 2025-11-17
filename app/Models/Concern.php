<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Concern extends Model
{
    protected $fillable = [
        'employee_id',
        'concern_category_id',
        'subject',
        'description',
        'status',
        'is_confidential'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function category()
    {
        return $this->belongsTo(ConcernCategory::class, 'concern_category_id');
    }

    public function replies()
    {
        return $this->hasMany(ConcernReply::class);
    }

    public function attachments()
    {
        return $this->hasMany(ConcernAttachment::class);
    }
}
