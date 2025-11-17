<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmployeeDocumentsController extends Controller
{
public function index(Request $request)
{
    $user = auth()->user();
    $deptId = $request->get('department_id');
    $search = $request->get('q');

    $query = Document::with(['employee.department', 'uploader'])
        ->when($user->hasRole('supervisor'), function ($q) use ($user) {
            if (method_exists($user, 'supervisedDepartments')) {
                $deptIds = $user->supervisedDepartments()->pluck('departments.id');
                $q->whereHas('employee', fn($e) => $e->whereIn('department_id', $deptIds));
            }
        })
        ->when($deptId, fn($q) =>
            $q->whereHas('employee', fn($e) => $e->where('department_id', $deptId))
        )
        ->when($search, function ($q) use ($search) {
            $q->whereHas('employee', function ($e) use ($search) {
                $e->where('name', 'like', "%{$search}%")
                  ->orWhere('employee_code', 'like', "%{$search}%");
            })->orWhere('title', 'like', "%{$search}%");
        });

    // ✅ Refined visibility filtering
    if ($user->hasRole('hr')) {
        // HR should NOT see employee-only or private_employee documents
        $query->whereNotIn('visibility', ['employee', 'private_employee']);
    } elseif ($user->hasRole('supervisor')) {
        // Supervisor can only see hr/supervisor-shared docs
        $query->whereNotIn('visibility', ['employee', 'private_employee', 'hr_supervisor']);
    } elseif ($user->hasRole('employee')) {
        // Employee sees only own documents
        $query->whereHas('employee', fn($e) => $e->where('user_id', $user->id));
    }

    $documents = $query->latest()->paginate(10);
    $departments = Department::orderBy('name')->get();

    return view('documents.index', compact('documents', 'departments', 'deptId', 'search'));
}


    public function show(Document $document)
    {
        $this->authorize('view', $document);
        return view('documents.show', compact('document'));
    }

    /** ✅ New: View / Preview Inline in Browser */
    public function view(Document $document)
    {
        $this->authorize('view', $document);

        $disk = Storage::disk('public');
        if (!$document->file_path || !$disk->exists($document->file_path)) {
            abort(404, 'File not found.');
        }

        $mime = $disk->mimeType($document->file_path) ?? 'application/octet-stream';
        $stream = $disk->readStream($document->file_path);

        // Display inline (for PDF, JPG, PNG)
        return response()->stream(function () use ($stream) {
            fpassthru($stream);
        }, 200, [
            'Content-Type'        => $mime,
            'Cache-Control'       => 'public, max-age=31536000, immutable',
            'Content-Disposition' => 'inline; filename="' . basename($document->file_path) . '"',
        ]);
    }

    public function download(Document $document)
    {
        $this->authorize('view', $document);

        $disk = Storage::disk('public');
        $path = $document->file_path;

        if (!$path || !$disk->exists($path)) {
            abort(404, 'File not found.');
        }

        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $filename = "{$document->title}." . ($ext ?: 'file');

        return $disk->download($path, $filename);
    }

    public function destroy(Document $document)
    {
        $this->authorize('delete', $document);

        $disk = Storage::disk('public');
        if ($document->file_path && $disk->exists($document->file_path)) {
            $disk->delete($document->file_path);
        }

        $document->delete();

        return back()->with('success', 'Document deleted successfully.');
    }
}
