<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EmployeeRegularized extends Notification
{
    use Queueable;

    protected $employee;

    public function __construct($employee)
    {
        $this->employee = $employee;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

  public function toDatabase($notifiable)
{
    $isEmployee = $notifiable->hasRole('employee');

    return [
        'title' => $isEmployee
            ? 'Congratulations! You are now Regular'
            : 'Employee Regularized',

        'message' => $isEmployee
            ? "You are now a regular employee effective today. Keep up the great work!"
            : "{$this->employee->full_name} ({$this->employee->employee_code}) has been regularized today.",

        'employee_id' => $this->employee->id,

        'url' => $isEmployee
            ? route('profile.edit')  // or any route you prefer
            : route('employees.show', $this->employee->id),

        'date' => now()->toDateString(),
    ];
}

}
