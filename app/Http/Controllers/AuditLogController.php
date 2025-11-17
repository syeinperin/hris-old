<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\LoginAudit; // optional, only if you also have this model

class AuditLogController extends Controller
{
    /**
     * Display a paginated list of users and their last login timestamps.
     */
    public function index(Request $request)
    {
        $query = User::query()
            ->whereNotNull('last_login')
            ->orderByDesc('last_login');

        // 🔍 Apply search filter
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Load roles for display
        $logs = $query->with('roles')
            ->paginate(15)
            ->withQueryString();

        return view('audit-logs.index', compact('logs'));
    }

    protected static function booted()
{
    static::created(function ($model) {
        AuditLog::create([
            'user_id'        => auth()->id(),
            'action'         => 'created',
            'auditable_type' => get_class($model),
            'auditable_id'   => $model->id,
            'new_values'     => $model->getAttributes(),
        ]);
    });

    static::updated(function ($model) {
        AuditLog::create([
            'user_id'        => auth()->id(),
            'action'         => 'updated',
            'auditable_type' => get_class($model),
            'auditable_id'   => $model->id,
            'old_values'     => $model->getOriginal(),
            'new_values'     => $model->getChanges(),
        ]);
    });

    static::deleted(function ($model) {
        AuditLog::create([
            'user_id'        => auth()->id(),
            'action'         => 'deleted',
            'auditable_type' => get_class($model),
            'auditable_id'   => $model->id,
            'old_values'     => $model->getOriginal(),
        ]);
    });
}

    /**
     * Show detailed audit logs for a single user (CRUD + login activities).
     */
    public function show($id)
    {
        $user = User::with('roles')->findOrFail($id);

        // 🔹 Combine CRUD audit logs and login history (if table exists)
        $crudLogs = AuditLog::where('user_id', $id)
            ->orderByDesc('created_at')
            ->get();

        // Optional login history, if you track logins separately
        $loginLogs = class_exists(LoginAudit::class)
            ? LoginAudit::where('user_id', $id)->orderByDesc('created_at')->get()
            : collect();

        // 🔹 Merge both logs into a unified collection (sorted by time)
        $merged = $crudLogs->merge($loginLogs)->sortByDesc('created_at');

        // 🔹 Paginate manually (since we merged collections)
        $perPage = 15;
        $page = request()->get('page', 1);
        $logs = new \Illuminate\Pagination\LengthAwarePaginator(
            $merged->forPage($page, $perPage)->values(),
            $merged->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('audit-logs.show', compact('user', 'logs'));
    }
}
