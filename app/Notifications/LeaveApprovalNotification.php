<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\LeaveRequest;
use Carbon\Carbon;

class LeaveApprovalNotification extends Notification
{
    use Queueable;

    protected $leave;
    protected $requester;

    public function __construct(LeaveRequest $leave)
    {
        $this->leave = $leave;
        $this->requester = $leave->user;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        // Format leave type and date range
        $type = ucfirst(strtolower($this->leave->type?->name ?? 'Leave'));

        $start = $this->formatDate($this->leave->start_date);
        $end   = $this->formatDate($this->leave->end_date);

        // If same day leave, simplify message
        $dateText = $start === $end
            ? "on {$start}"
            : "from {$start} to {$end}";

        return [
            'title'   => 'Leave Request Submitted',
            'message' => "{$this->requester->name} filed a {$type} {$dateText}.",
            'link'    => route('approvals.index'),
            'status'  => 'pending',
        ];
    }

    /**
     * Format dates to a cleaner style (e.g., Nov 10, 2025)
     * Returns '—' if date is invalid or null.
     */
    private function formatDate(?string $date): string
    {
        try {
            return $date ? Carbon::parse($date)->format('M j, Y') : '—';
        } catch (\Exception $e) {
            return '—';
        }
    }
}
