@extends('layouts.app')

@section('page_title', 'Employees')

@push('styles')
    <style>
        .card,
        .card-body,
        .table-responsive {
            overflow: visible !important;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
            font-size: 0.9rem;
        }

        .table th,
        .table td {
            padding: 0.65rem 0.75rem;
            vertical-align: middle;
            text-align: left;
            white-space: normal;
            word-break: break-word;
            border-top: 1px solid #e9ecef;
            line-height: 1.35;
        }

        .table th {
            font-weight: 600;
            font-size: 0.9rem;
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }

        .table th:first-child,
        .table td:first-child {
            width: 55px;
            text-align: center;
        }

        .table td:nth-child(1),
        .table td:nth-child(2) {
            font-size: 0.88rem;
            font-weight: 500;
        }

        .badge {
            font-size: 0.8rem;
            padding: 0.4rem 0.7rem;
            border-radius: 9999px;
            font-weight: 600;
        }

        .dropdown-menu {
            z-index: 1060 !important;
        }

        .dropdown {
            position: relative;
        }

        .pagination {
            justify-content: flex-end;
            margin: 0.75rem 1rem 0.25rem;
        }
    </style>
@endpush



@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif


@section('content')
    <div class="container-fluid">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="bi bi-people-fill me-2"></i> Employees
                </h4>
                <div class="d-flex align-items-center gap-2">
                    @role('hr')
                        <a href="{{ route('employees.endings') }}" class="btn btn-outline-warning btn-sm">
                            <i class="bi bi-exclamation-triangle me-1"></i> Contracts Ending
                            <span class="badge bg-warning text-dark">{{ $endingCount }}</span>
                        </a>
                        <a href="{{ route('employees.inactive') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-person-x me-1"></i> Inactive
                            <span class="badge bg-secondary">{{ $inactiveCount }}</span>
                        </a>
                        <a href="{{ route('departments.index') }}" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-building me-1"></i> Departments
                        </a>
                        <a href="{{ route('employees.create') }}" class="btn btn-success btn-sm">
                            <i class="bi bi-plus-lg me-1"></i> Add
                        </a>
                    @endrole

                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif


            <div class="px-3 pt-3 pb-1 filter-bar">
                <x-search-bar :action="route('employees.index')" placeholder="Search name, code or email…" :filters="[
                    'department_id' => $departments,
                    'employment_type' => $employmentTypes,
                ]" />
            </div>

            <div class="card-body pt-2">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 table-sticky">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Code</th>
                                <th>Status</th>
                                <th>Offboarding</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Dept</th>
                                <th>Type</th>
                                <th>Schedule</th>
                                <th class="text-center" style="width:56px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($employees as $e)
                                @php
                                    $payload = [
                                        // ─── Core Identifiers ─────────────────────────────
                                        'id' => $e->id,
                                        'employee_code' => $e->employee_code,
                                        'status' => $e->status,

                                        // ─── User Account ───────────────────────────────
                                        'email' => optional($e->user)->email,
                                        'role' => optional($e->user)->getRoleNames()->first(),
                                        'employment_status' => ucfirst($e->status),

                                        // ─── Work Info ─────────────────────────────────
                                        'department' => optional($e->department)->name,
                                        'designation' => optional($e->designation)->name,
                                        'schedule_name' => optional($e->schedule)->name,
                                        'schedule_in' => optional($e->schedule)->time_in,
                                        'schedule_out' => optional($e->schedule)->time_out,
                                        'employment_type' => $e->employment_type,
                                        'employment_start_date' => optional($e->employment_start_date)->format('Y-m-d'),
                                        'employment_end_date' => optional($e->employment_end_date)->format('Y-m-d'),

                                        // ─── Personal ──────────────────────────────────
                                        'first_name' => $e->first_name,
                                        'middle_name' => $e->middle_name,
                                        'last_name' => $e->last_name,
                                        'gender' => $e->gender,
                                        'dob' => $e->dob,
                                        'birth_place' => $e->birth_place,
                                        'civil_status' => $e->civil_status,
                                        'religion' => $e->religion,
                                        'spouse' => $e->spouse,
                                        'occupation' => $e->occupation,
                                        'name_of_children' => $e->name_of_children,
                                        'children_birth_date' => $e->children_birth_date,
                                        'father_name' => $e->father_name,
                                        'mother_name' => $e->mother_name,
                                        'father_occupation' => $e->father_occupation,
                                        'mother_occupation' => $e->mother_occupation,
                                        'languages_spoken' => $e->languages_spoken,

                                        // ─── Addresses ─────────────────────────────────
                                        'current_street_address' => $e->current_street_address,
                                        'current_barangay' => $e->current_barangay,
                                        'current_city' => $e->current_city,
                                        'current_province' => $e->current_province,
                                        'current_postal_code' => $e->current_postal_code,
                                        'permanent_address' => $e->permanent_address,

                                        // ─── Emergency Contact ─────────────────────────
                                        'emergency_contact_name' => $e->emergency_contact_name,
                                        'emergency_contact_address' => $e->emergency_contact_address,
                                        'emergency_contact_phone' => $e->emergency_contact_phone,

                                        // ─── Government IDs ────────────────────────────
                                        'sss_no' => $e->sss_no,
                                        'tin_no' => $e->tin_no,
                                        'pagibig_id_no' => $e->pagibig_id_no,
                                        'philhealth_tin_id_no' => $e->philhealth_tin_id_no,
                                        'gsis_id_no' => $e->gsis_id_no,
                                        'agency_employee_no' => $e->agency_employee_no,

                                        // ─── Education ─────────────────────────────────
                                        'elementary_school' => $e->elementary_school,
                                        'elementary_year_graduated' => $e->elementary_year_graduated,
                                        'high_school' => $e->high_school,
                                        'high_school_year_graduated' => $e->high_school_year_graduated,
                                        'college' => $e->college,
                                        'college_year_graduated' => $e->college_year_graduated,
                                        'degree_received' => $e->degree_received,
                                        'special_skills' => $e->special_skills,

                                        // ─── Work History ──────────────────────────────
                                        'emp1_company' => $e->emp1_company,
                                        'emp1_position' => $e->emp1_position,
                                        'emp1_from' => $e->emp1_from,
                                        'emp1_to' => $e->emp1_to,
                                        'emp2_company' => $e->emp2_company,
                                        'emp2_position' => $e->emp2_position,
                                        'emp2_from' => $e->emp2_from,
                                        'emp2_to' => $e->emp2_to,

                                        // ─── Character References ─────────────────────
                                        'char1_name' => $e->char1_name,
                                        'char1_position' => $e->char1_position,
                                        'char1_company' => $e->char1_company,
                                        'char1_contact' => $e->char1_contact,
                                        'char2_name' => $e->char2_name,
                                        'char2_position' => $e->char2_position,
                                        'char2_company' => $e->char2_company,
                                        'char2_contact' => $e->char2_contact,

                                        // ─── Files (with URLs) ─────────────────────────────────────
                                        'profile_picture_url' => $e->profile_picture_url,
                                        'resume_url' => $e->resume_url,
                                        'mdr_philhealth_url' => $e->mdr_philhealth_url,
                                        'mdr_sss_url' => $e->mdr_sss_url,
                                        'mdr_pagibig_url' => $e->mdr_pagibig_url,
                                        'medical_documents_urls' => $e->medical_documents_urls,
                                    ];

                                    $dropUp = $loop->count - $loop->iteration < 3;
                                @endphp

                                <tr data-id="{{ $e->id }}">
                                    <td>{{ $e->id }}</td>
                                    <td>{{ $e->employee_code }}</td>
                                    <td><span class="badge bg-primary rounded-pill">{{ ucfirst($e->status) }}</span></td>

                                    <td>
                                        @php
                                            $o = $e->latestOffboarding;
                                            $final = in_array(optional($o)->status, ['completed', 'cancelled'], true);
                                            $map = [
                                                'draft' => 'secondary',
                                                'pending_clearance' => 'warning',
                                                'scheduled' => 'info',
                                                'awaiting_approvals' => 'primary',
                                                'completed' => 'success',
                                                'cancelled' => 'dark',
                                            ];
                                        @endphp
                                        @if ($o)
                                            <a href="{{ route('offboarding.show', $o) }}" class="text-decoration-none">
                                                <span class="badge bg-{{ $map[$o->status] ?? 'secondary' }}">
                                                    {{ ucfirst(str_replace('_', ' ', $o->status)) }}
                                                </span>
                                                @unless ($final)
                                                    <span class="text-muted small">(#{{ $o->id }})</span>
                                                @endunless
                                            </a>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>

                                    <td>{{ $e->name }}</td>
                                    <td>{{ optional($e->user)->email }}</td>
                                    <td>{{ optional($e->department)->name }}</td>
                                    <td>{{ ucfirst($e->employment_type) }}</td>
                                    <td>{{ optional($e->schedule)->time_in }}–{{ optional($e->schedule)->time_out }}</td>

                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button class="btn btn-outline-primary btn-sm" type="button"
                                                id="dropdown{{ $e->id }}" data-bs-toggle="dropdown"
                                                aria-expanded="false">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end"
                                                aria-labelledby="dropdown{{ $e->id }}">
                                                <li>
                                                    <button class="dropdown-item" type="button" data-bs-toggle="modal"
                                                        data-bs-target="#viewEmployeeModal"
                                                        data-employee='@json($payload)'>
                                                        <i class="bi bi-eye me-2"></i> View
                                                    </button>
                                                </li>

                                                @role(['hr', 'supervisor'])
                                                    <li>
                                                        <a class="dropdown-item"
                                                            href="{{ route('offboarding.create', ['employee_id' => $e->id]) }}">
                                                            <i class="bi bi-box-arrow-right me-2"></i> Start Offboarding
                                                        </a>
                                                    </li>

                                                    <li>
                                                        <hr class="dropdown-divider">
                                                    </li>

                                                    <li>
                                                        <form action="{{ route('employees.destroy', $e) }}" method="POST"
                                                            onsubmit="return confirm('Are you sure?')" style="margin: 0;">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="dropdown-item text-danger">
                                                                <i class="bi bi-trash me-2"></i> Delete
                                                            </button>
                                                        </form>
                                                    </li>
                                                @endrole
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4">
                    <small class="text-muted">
                        Showing {{ $employees->firstItem() }}–{{ $employees->lastItem() }} of {{ $employees->total() }}
                    </small>
                    {{ $employees->withQueryString()->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>

    @include('employees.show')
    @include('employees.edit-modal')

@endsection

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
