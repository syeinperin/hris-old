<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConcernReply extends Model
{
    protected $fillable = [
        'concern_id',
        'user_id',
        'message'
    ];

    public function concern()
    {
        return $this->belongsTo(Concern::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
