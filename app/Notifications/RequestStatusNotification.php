<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RequestStatusNotification extends Notification
{
    use Queueable;

    public string $title;
    public string $message;
    public string $status;

    public function __construct(string $title, string $message, string $status)
    {
        $this->title = $title;
        $this->message = $message;
        $this->status = $status;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'status' => $this->status,
        ];
    }
}
