<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Employee;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Requests\DocumentStoreRequest;
use App\Http\Requests\DocumentUpdateRequest;

class MyDocumentsController extends Controller
{
    public function __construct()
    {
        // ✅ Removed auto-authorization; use manual checks instead
    }

    /* =========================================================
     * INDEX: List all documents owned by the employee
     * ========================================================= */
    public function index()
    {
        $user = auth()->user();
        $employee = $user->employee ?? Employee::where('user_id', $user->id)->first();
        abort_unless($employee, 403, 'Employee profile required.');

        $docs = Document::mine($employee->id)->latest()->paginate(12);

        $counts = [
            'resume'  => Document::mine($employee->id)->where('doc_type', 'resume')->count(),
            'medical' => Document::mine($employee->id)->where('doc_type', 'medical')->count(),
            'mdr'     => Document::mine($employee->id)->whereIn('doc_type', ['mdr_philhealth', 'mdr_sss', 'mdr_pagibig'])->count(),
            'other'   => Document::mine($employee->id)->where('doc_type', 'other')->count(),
        ];

        return view('my-documents.index', compact('docs', 'counts', 'employee'));
    }

    /* =========================================================
     * CREATE
     * ========================================================= */
    public function create()
    {
        abort_unless(auth()->user()->employee, 403);
        return view('my-documents.create');
    }

    /* =========================================================
     * STORE
     * ========================================================= */
    public function store(DocumentStoreRequest $request)
    {
        $user = auth()->user();
        $employee = $user->employee ?? Employee::where('user_id', $user->id)->first();
        abort_unless($employee, 403);

        $latest = Document::mine($employee->id)
            ->where('doc_type', $request->doc_type)
            ->where('title', $request->title)
            ->orderByDesc('version')
            ->first();

        $version = $latest ? $latest->version + 1 : 1;
        $path = $request->file('file')->store("documents/{$employee->id}", 'public');

        $doc = Document::create([
            'employee_id' => $employee->id,
            'uploaded_by' => $user->id,
            'title'       => $request->title,
            'doc_type'    => $request->doc_type,
            'file_path'   => $path,
            'version'     => $version,
            'notes'       => $request->notes,
            'visibility'  => $request->visibility ?? 'employee',
            'expires_at'  => $request->expires_at,
        ]);

        $this->mirrorToEmployeeFields($employee, $doc->doc_type, $path);

        return redirect()->route('mydocs.index')->with('success', 'Document uploaded successfully.');
    }

    /* =========================================================
     * SHOW
     * ========================================================= */
    public function show(Document $document)
    {
        $user = auth()->user();
        $employee = $user->employee ?? Employee::where('user_id', $user->id)->first();

        if (
            ($employee && $employee->id === $document->employee_id) ||
            $user->hasAnyRole(['hr', 'supervisor'])
        ) {
            return view('my-documents.show', compact('document'));
        }

        abort(403, 'Unauthorized access.');
    }

    /* =========================================================
     * EDIT
     * ========================================================= */
    public function edit(Document $document)
    {
        $user = auth()->user();
        $employee = $user->employee ?? Employee::where('user_id', $user->id)->first();

        if (
            ($employee && $employee->id === $document->employee_id) ||
            $user->hasRole('hr')
        ) {
            return view('my-documents.edit', compact('document'));
        }

        abort(403, 'Unauthorized access.');
    }

    /* =========================================================
     * UPDATE
     * ========================================================= */
    public function update(DocumentUpdateRequest $request, Document $document)
    {
        $user = auth()->user();
        $employee = $user->employee ?? Employee::where('user_id', $user->id)->first();

        if (
            !($employee && $employee->id === $document->employee_id) &&
            !$user->hasRole('hr')
        ) {
            abort(403, 'Unauthorized access.');
        }

        $data = $request->validated();

        if ($request->hasFile('file')) {
            if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }

            $data['file_path'] = $request->file('file')->store("documents/{$document->employee_id}", 'public');
            $data['version']   = $document->version + 1;

            $this->mirrorToEmployeeFields($document->employee, $request->doc_type, $data['file_path']);
        }

        $document->update($data);
        return redirect()->route('mydocs.show', $document)->with('success', 'Document updated successfully.');
    }

    /* =========================================================
     * DESTROY
     * ========================================================= */
    public function destroy(Document $document)
    {
        $user = auth()->user();
        $employee = $user->employee ?? Employee::where('user_id', $user->id)->first();

        if (
            ($employee && $employee->id === $document->employee_id) ||
            $user->hasRole('hr')
        ) {
            if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }
            $document->delete();

            return redirect()->route('mydocs.index')->with('success', 'Document deleted successfully.');
        }

        abort(403, 'Unauthorized access.');
    }

    /* =========================================================
     * DOWNLOAD DOCUMENT FILE
     * ========================================================= */
    public function download(Document $document)
    {
        $user = auth()->user();
        $employee = $user->employee ?? Employee::where('user_id', $user->id)->first();

        if (
            ($employee && $employee->id === $document->employee_id) ||
            $user->hasAnyRole(['hr', 'supervisor'])
        ) {
            $disk = Storage::disk('public');
            abort_unless($disk->exists($document->file_path), 404);

            $ext = pathinfo($document->file_path, PATHINFO_EXTENSION) ?: 'file';
            return $disk->download($document->file_path, "{$document->title}.{$ext}");
        }

        abort(403, 'Unauthorized access.');
    }

    /* =========================================================
     * PDF DOWNLOADS (CoE & EIS)
     * ========================================================= */
 public function downloadCoE()
{
    $user = auth()->user();
    $employee = $user->employee ?? Employee::where('user_id', $user->id)->first();
    abort_unless($employee, 403, 'Employee profile not found.');

        $pdf = Pdf::loadView('reports.pdf.certificate', compact('employee'))->setPaper('A4', 'portrait');
        return $pdf->download("Certificate_of_Employment_{$employee->employee_code}.pdf");

       
    }

    public function downloadEIS()
    {
        $user = auth()->user();
        $employee = $user->employee ?? Employee::where('user_id', $user->id)->first();
        abort_unless($employee, 403, 'Employee profile not found.');

        $pdf = Pdf::loadView('reports.pdf.employee_sheet', compact('employee'))->setPaper('A4', 'portrait');
        return $pdf->download("Employee_Information_Sheet_{$employee->employee_code}.pdf");
    }

    /* =========================================================
     * SELF-SERVICE VIEW
     * ========================================================= */
    public function selfService()
    {
        abort_unless(auth()->check(), 403);
        return view('my-documents.self-service');
    }

    /* =========================================================
     * HELPER: Mirror uploaded document into employee record
     * ========================================================= */
    private function mirrorToEmployeeFields($employee, string $docType, string $storedPath): void
    {
        if ($docType === 'resume') {
            $employee->update(['resume_file' => $storedPath]);
            return;
        }

        if (in_array($docType, ['mdr_philhealth', 'mdr_sss', 'mdr_pagibig'], true)) {
            $map = [
                'mdr_philhealth' => 'mdr_philhealth_file',
                'mdr_sss'        => 'mdr_sss_file',
                'mdr_pagibig'    => 'mdr_pagibig_file',
            ];
            $employee->update([$map[$docType] => $storedPath]);
            return;
        }

        if ($docType === 'medical') {
            $docs = $employee->medical_documents ?? [];
            $docs[] = $storedPath;
            $employee->update(['medical_documents' => array_values(array_unique($docs))]);
        }
    }
}
