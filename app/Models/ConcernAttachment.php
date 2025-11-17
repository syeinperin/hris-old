<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConcernAttachment extends Model
{
    protected $fillable = [
        'concern_id',
        'file_path',
        'file_name'
    ];

    public function concern()
    {
        return $this->belongsTo(Concern::class);
    }
}
