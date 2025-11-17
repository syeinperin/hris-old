<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;
use App\Models\Concern;

class ConcernReplyNotification extends Notification
{
    use Queueable;

    protected $concern;
    protected $replyMessage;

    public function __construct(Concern $concern, $replyMessage)
    {
        $this->concern = $concern;
        $this->replyMessage = $replyMessage;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'concern_id' => $this->concern->id,
            'subject'    => $this->concern->subject,
            'message'    => $this->replyMessage,
            'employee'   => $this->concern->employee->full_name,
        ];
    }
}
