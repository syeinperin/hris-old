<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    // ✅ Employees under this department
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    // ✅ Supervisors assigned to this department
   public function supervisors()
{
    return $this->belongsToMany(User::class, 'department_supervisor', 'department_id', 'supervisor_id')
                ->withTimestamps();
}

}
