<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use App\Models\Discipline\InfractionReport;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()
            ->notifications()
            ->latest()
            ->paginate(10);

        return view('notifications.index', compact('notifications'));
    }

    public function show($id)
    {
        $note = auth()->user()
            ->notifications()
            ->findOrFail($id);

        // Mark as read
        if (!$note->read_at) {
            $note->markAsRead();
        }

        $data = $note->data;

        // 1. If infraction → open HR disciplinary view
        if (Arr::has($data, 'infraction_id')) {
            $infraction = InfractionReport::with('actions.type')
                ->findOrFail($data['infraction_id']);

            return view('notifications.show', compact('note', 'infraction'));
        }

        // 2. If notification has its own URL → go there
        if (!empty($data['url'])) {
            return redirect($data['url']);
        }

        // 3. If EMPLOYEE → go to employee dashboard
        if (auth()->user()->role->name === 'employee') {
            return redirect()->route('dashboard.employee');
        }

        // 4. If HR → go to approvals
        if (auth()->user()->role->name === 'hr') {
            return redirect()->route('approvals.index');
        }

        // 5. Fallback for other roles
        return redirect()->route('dashboard');
    }

    public function markRead($id)
    {
        $note = auth()->user()
            ->notifications()
            ->findOrFail($id);

        $note->markAsRead();

        return back();
    }

    public function markAllRead()
    {
        auth()->user()->unreadNotifications->markAsRead();

        return back();
    }
}
