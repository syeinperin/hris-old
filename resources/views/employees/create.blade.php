{{-- resources/views/employees/create.blade.php --}}
@extends('layouts.app')

@section('page_title', 'Add Employee')

@section('content')
  <div class="container py-2">

    <div class="page-head">
      <div>
        <h2>Add Employee</h2>
        <div class="page-sub">Create the account, fill in details, and (optionally) capture a face template.</div>
      </div>
      <a href="{{ route('employees.index') }}" class="btn btn-outline-primary">
        <i class="bi bi-arrow-left"></i> Back to list
      </a>
    </div>

    {{-- ===== Server-side notices ===== --}}
    @if(session('error'))
      <div class="alert alert-danger mb-3"><strong>Error:</strong> {!! nl2br(e(session('error'))) !!}</div>
    @endif
    @if ($errors->any())
      <div class="alert alert-info mb-3 shadow-sm" style="border-left: 4px solid #0d6efd;">
        <div class="fw-bold mb-1">Please correct the following errors:</div>
        <ul class="mb-0">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    {{-- ===== Client-side upload sanity (php.ini + live file list) ===== --}}
    @php
      $phpFileOn = (bool) ini_get('file_uploads');
      $phpMaxFile = ini_get('upload_max_filesize') ?: '—';
      $phpMaxPost = ini_get('post_max_size') ?: '—';
      $phpMaxFiles = ini_get('max_file_uploads') ?: '—';
    @endphp
    <div class="alert {{ $phpFileOn ? 'alert-info' : 'alert-danger' }} mb-3">
      <div class="fw-semibold mb-1">
        File uploads {{ $phpFileOn ? 'are enabled' : 'are <u>disabled</u>' }} on this server.
      </div>
      <div class="small">
        <span class="me-3">upload_max_filesize: <code>{{ $phpMaxFile }}</code></span>
        <span class="me-3">post_max_size: <code>{{ $phpMaxPost }}</code></span>
        <span>max_file_uploads: <code>{{ $phpMaxFiles }}</code></span>
      </div>
      <div id="cl-upload-warning" class="mt-2 text-danger small d-none"></div>
    </div>
    <div id="cl-file-summary" class="alert alert-secondary small d-none mb-3">
      <div class="fw-semibold mb-1">Files queued for upload</div>
      <ul id="cl-file-list" class="mb-0"></ul>
    </div>

    <form action="{{ route('employees.store') }}" method="POST" enctype="multipart/form-data" id="empCreateForm"
      class="form-compact">
      @csrf

      {{-- Tabs --}}
      <nav class="tabs" id="tabs">
        <a href="#tab-face">Face</a>
        <a href="#tab-personal" class="active">Personal</a>
        <a href="#tab-account">Account</a>
        <a href="#tab-work">Work</a>
        <a href="#tab-benefits">Benefits</a>
        <a href="#tab-education">Education</a>
        <a href="#tab-employment">Employment History</a>
        <a href="#tab-refs">Character References</a>
        <a href="#tab-docs">Certificates & Docs</a>
      </nav>

      {{-- FACE --}}
      <section id="tab-face" class="tab-panel" hidden>
        <div class="panel">
          <div class="row g-12">
            <div class="col-lg-8">
              <h5 class="mb-2 fw-bold">Live Camera</h5>
              <div id="stage" class="ratio ratio-16x9 face-stage">
                <video id="video" autoplay muted playsinline style="width:100%;height:100%;object-fit:cover;"></video>
              </div>
              <div class="d-flex gap-2 mt-2">
                <button type="button" id="btnStart" class="btn btn-outline-primary"><i class="bi bi-camera-video"></i>
                  Start</button>
                <button type="button" id="btnCapture" class="btn" disabled><i class="bi bi-record-circle"></i>
                  Capture</button>
              </div>
              <small id="camStatus" class="text-muted d-block mt-2 help-minor"></small>
              <input type="hidden" name="face_descriptor" id="face_descriptor">
              <input type="hidden" name="face_image_base64" id="face_image_base64">
            </div>
            <div class="col-lg-4">
              <h5 class="mb-2 fw-bold">Preview</h5>
              <div class="ratio ratio-4x3 preview-frame">
                <canvas id="facePreview" width="600" height="800" style="width:100%;height:100%"></canvas>
              </div>
            </div>
          </div>
        </div>
      </section>

      {{-- PERSONAL --}}
      <section id="tab-personal" class="tab-panel">
        <div class="panel">
          <div class="form-grid">

            {{-- Names --}}
            <div class="form-floating span-4">
              <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror"
                placeholder="First Name *" value="{{ old('first_name') }}" required>
              <label>First Name *</label>
            </div>
            <div class="form-floating span-4">
              <input type="text" name="middle_name" class="form-control" placeholder="Middle Name"
                value="{{ old('middle_name') }}">
              <label>Middle Name</label>
            </div>
            <div class="form-floating span-4">
              <input type="text" name="last_name" id="last_name"
                class="form-control @error('last_name') is-invalid @enderror" placeholder="Last Name *"
                value="{{ old('last_name') }}" required>
              <label>Last Name *</label>
            </div>

            <div class="span-12">
              <h6 class="fw-bold mb-0 mt-1">Current Address</h6>
            </div>

            {{-- Street --}}
            <div class="form-floating span-12">
              <input type="text" name="current_street_address"
                class="form-control @error('current_street_address') is-invalid @enderror" placeholder="Street Address *"
                value="{{ old('current_street_address') }}" required>
              <label>Street Address *</label>
            </div>

            {{-- Province / City / Barangay --}}
            <div class="form-floating span-4">
              <select id="current_province" name="current_province"
                class="form-select @error('current_province') is-invalid @enderror" required>
                <option value="" disabled {{ old('current_province') ? '' : 'selected' }}>Province *</option>
                @foreach($philippineProvinces as $prov)
                  <option value="{{ $prov }}" {{ old('current_province') == $prov ? 'selected' : '' }}>{{ $prov }}</option>
                @endforeach
              </select>
              <label>Province *</label>
            </div>

            <div class="form-floating span-4">
              <select id="current_city_select" class="form-select @error('current_city') is-invalid @enderror" required>
                <option value="" disabled selected>Select City / Municipality</option>
              </select>
              <label>City *</label>
              <input type="hidden" name="current_city" id="current_city" value="{{ old('current_city') }}">
            </div>

            <div class="form-floating span-4">
              <select id="current_barangay_select" class="form-select @error('current_barangay') is-invalid @enderror"
                required>
                <option value="" disabled selected>Select Barangay</option>
              </select>
              <label>Barangay *</label>
              <input type="hidden" name="current_barangay" id="current_barangay" value="{{ old('current_barangay') }}">
            </div>

            {{-- ZIP / Gender / DOB --}}
            <div class="form-floating span-3">
              <input type="text" id="current_postal_code" name="current_postal_code"
                class="form-control @error('current_postal_code') is-invalid @enderror" placeholder="ZIP Code"
                value="{{ old('current_postal_code') }}">
              <label>ZIP Code</label>
            </div>

            <div class="form-floating span-3">
              <select name="gender" class="form-select @error('gender') is-invalid @enderror" required>
                <option value="" disabled {{ old('gender') ? '' : 'selected' }}>Gender *</option>
                <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>Other</option>
              </select>
              <label>Gender *</label>
            </div>

            <div class="form-floating span-3">
              <input type="date" name="dob" id="dob" class="form-control @error('dob') is-invalid @enderror"
                placeholder="Date of Birth *" value="{{ old('dob') }}" required>
              <label>Date of Birth *</label>
            </div>

            {{-- Profile picture (full row) --}}
            <div class="span-12">
              <label class="form-label fw-bold">Profile Picture</label>
              <div class="d-flex gap-2 flex-wrap">
                <input type="file" name="profile_picture" id="profile_picture_file"
                  class="form-control flex-grow-1 @error('profile_picture') is-invalid @enderror" accept="image/*">
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal"
                  data-bs-target="#cameraModal">
                  <i class="bi bi-camera-video"></i> Use Camera
                </button>
              </div>
              @error('profile_picture')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
              <input type="hidden" name="profile_picture_camera" id="profile_picture_camera">
            </div>

            {{-- Birth place / Civil status --}}
            <div class="form-floating span-6">
              <input type="text" name="birth_place" class="form-control" placeholder="Birth Place"
                value="{{ old('birth_place') }}">
              <label>Birth Place</label>
            </div>
            <div class="form-floating span-6">
              <select name="civil_status" id="civil_status" class="form-select">
                <option value="">—</option>
                <option value="single" {{ old('civil_status') == 'single' ? 'selected' : '' }}>Single</option>
                <option value="married" {{ old('civil_status') == 'married' ? 'selected' : '' }}>Married</option>
                <option value="widowed" {{ old('civil_status') == 'widowed' ? 'selected' : '' }}>Widowed</option>
                <option value="separated" {{ old('civil_status') == 'separated' ? 'selected' : '' }}>Separated</option>
                <option value="other" {{ old('civil_status') == 'other' ? 'selected' : '' }}>Other</option>
              </select>
              <label>Civil Status</label>
            </div>

            {{-- Parents --}}
            <div class="form-floating span-6">
              <input type="text" name="father_name" class="form-control" placeholder="Father's Name"
                value="{{ old('father_name') }}">
              <label>Father's Name</label>
            </div>
            <div class="form-floating span-6">
              <input type="text" name="mother_name" class="form-control" placeholder="Mother's Name"
                value="{{ old('mother_name') }}">
              <label>Mother's Name</label>
            </div>

            {{-- Emergency contact --}}
            <div class="form-floating span-6">
              <input type="text" name="emergency_contact_name" class="form-control" placeholder="Emergency Contact Person"
                value="{{ old('emergency_contact_name') }}">
              <label>Emergency Contact Person</label>
            </div>
            <div class="form-floating span-6">
              <input type="tel" name="contact_number" class="form-control @error('contact_number') is-invalid @enderror"
                placeholder="09171234567" pattern="^(09\d{9}|9\d{9}|639\d{9}|\+639\d{9})$"
                title="Enter a valid PH mobile: 0917xxxxxxx" required placeholder="Emergency Contact Number"
                value="{{ old('emergency_contact_number') }}">
              <label>Emergency Contact Number</label>
            </div>

            {{-- Spouse (only if married) --}}
            <div id="spouseRow" class="span-12" hidden>
              <div class="form-grid">
                <div class="form-floating span-6">
                  <input type="text" name="spouse_name" id="spouse_name" class="form-control" placeholder="Spouse Name"
                    value="{{ old('spouse_name') }}">
                  <label>Spouse Name</label>
                </div>
                <div class="form-floating span-6">
                  <input type="tel" name="spouse_contact" id="spouse_contact" class="form-control"
                    placeholder="Spouse Contact" value="{{ old('spouse_contact') }}">
                  <label>Spouse Contact</label>
                </div>
              </div>
            </div>

          </div>
        </div>
      </section>

      {{-- ACCOUNT --}}
      <section id="tab-account" class="tab-panel" hidden>
        <div class="panel">
          <div class="row g-12 align-items-end">
            <div class="col-md-4 form-floating">
              <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                placeholder="Email" value="{{ old('email') }}">
              <label>Email</label>
            </div>
            <div class="col-md-4 form-floating">
              <input type="tel" name="contact_number" class="form-control @error('contact_number') is-invalid @enderror"
                placeholder="09171234567" pattern="^(09\d{9}|9\d{9}|639\d{9}|\+639\d{9})$"
                title="Enter a valid PH mobile: 0917xxxxxxx" required>
              <label>Contact Number</label>
            </div>
            <div class="col-md-2 form-floating">
              <select name="role" class="form-select @error('role') is-invalid @enderror" required>
                <option value="" disabled selected>-- Select Role --</option>
                @foreach($roles as $r)
                  <option value="{{ $r }}" {{ old('role') == $r ? 'selected' : '' }}>{{ ucfirst($r) }}</option>
                @endforeach
              </select>
              <label>Role *</label>
            </div>
            <div class="col-md-2"></div>

            <div class="col-md-4 form-floating">
              <input type="password" name="password" id="password"
                class="form-control @error('password') is-invalid @enderror" placeholder="Password *" required>
              <label>Password *</label>
            </div>
            <div class="col-md-4 form-floating">
              <input type="password" name="password_confirmation" id="password_confirm" class="form-control"
                placeholder="Confirm *" required>
              <label>Confirm *</label>
            </div>
            <div class="col-md-4">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="autoPwd" checked>
                <label class="form-check-label" for="autoPwd">Auto-generate</label>
              </div>
            </div>
          </div>
        </div>
      </section>

      {{-- WORK --}}
      <section id="tab-work" class="tab-panel" hidden>
        <div class="panel">
          <div class="row g-12">
            <div class="col-md-4 form-floating">
              <select name="department_id" class="form-select @error('department_id') is-invalid @enderror" required>
                <option value="" disabled {{ old('department_id') ? '' : 'selected' }}>Select Department *</option>
                @foreach($departments as $id => $name)
                  <option value="{{ $id }}" {{ old('department_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
              </select>
              <label>Department *</label>

            </div>
            <div class="col-md-4 form-floating">
              <select name="designation_id" class="form-select @error('designation_id') is-invalid @enderror" required>
                <option value="" disabled selected>Designation *</option>
                @foreach($designations as $id => $name)
                  <option value="{{ $id }}" {{ old('designation_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
              </select>
              <label>Designation *</label>
            </div>

            {{-- NOTE: Schedule selection removed. Supervisors assign shifts in Schedule page. --}}
            <div class="col-md-4">
              <div class="alert alert-info small mb-0">
                <div class="fw-semibold">Schedule is assigned by Supervisors</div>
                <div>Use <strong>Schedule → Assign Shifts</strong> after saving this employee.</div>
              </div>
            </div>

            <div class="col-md-4 form-floating">
              <select name="employment_type" id="employment_type"
                class="form-select @error('employment_type') is-invalid @enderror" required>
                <option value="" disabled {{ old('employment_type') ? '' : 'selected' }}>Employment Type *</option>
                @foreach($employmentTypes as $k => $lbl)
                  <option value="{{ $k }}" {{ old('employment_type') == $k ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
              </select>
              <label>Employment Type *</label>
            </div>
            <div class="col-md-4 form-floating">
              <input type="date" name="employment_start_date" id="employment_start_date"
                class="form-control @error('employment_start_date') is-invalid @enderror" placeholder="Start Date *"
                value="{{ old('employment_start_date') }}" required>
              <label>Start Date *</label>
            </div>
            <div class="col-md-4 form-floating">
              <input type="date" name="employment_end_date" id="employment_end_date"
                class="form-control @error('employment_end_date') is-invalid @enderror" placeholder="End Date"
                value="{{ old('employment_end_date') }}">
              <label id="endDateLabel">End Date</label>
            </div>
          </div>
        </div>
      </section>

      {{-- BENEFITS --}}
      <section id="tab-benefits" class="tab-panel" hidden>
        <div class="panel">
          <div class="row g-12">
            <div class="col-md-4 form-floating">
              <input type="text" name="gsis_id_no" class="form-control" placeholder="GSIS ID No."
                value="{{ old('gsis_id_no') }}">
              <label>GSIS ID No.</label>
            </div>
            <div class="col-md-4 form-floating">
              <input type="text" name="pagibig_id_no" class="form-control" placeholder="PAGIBIG ID No."
                value="{{ old('pagibig_id_no') }}">
              <label>PAGIBIG ID No.</label>
            </div>
            <div class="col-md-4 form-floating">
              <input type="text" name="philhealth_tin_id_no" class="form-control" placeholder="PHILHEALTH TIN ID No."
                value="{{ old('philhealth_tin_id_no') }}">
              <label>PHILHEALTH TIN ID No.</label>
            </div>
            <div class="col-md-4 form-floating">
              <input type="text" name="sss_no" class="form-control" placeholder="SSS No." value="{{ old('sss_no') }}">
              <label>SSS No.</label>
            </div>
            <div class="col-md-4 form-floating">
              <input type="text" name="tin_no" class="form-control" placeholder="TIN No." value="{{ old('tin_no') }}">
              <label>TIN No.</label>
            </div>
            <div class="col-md-4 form-floating">
              <input type="text" name="agency_employee_no" class="form-control" placeholder="Agency Emp. No."
                value="{{ old('agency_employee_no') }}">
              <label>Agency Emp. No.</label>
            </div>
          </div>
        </div>
      </section>

      {{-- EDUCATION --}}
      <section id="tab-education" class="tab-panel" hidden>
        <div class="panel">
          <div class="row g-12">
            <div class="col-12">
              <h6 class="fw-bold">🎓 Educational Background</h6>
            </div>
            <div class="col-md-6 form-floating">
              <input type="text" name="elementary_school" class="form-control" placeholder="Elementary School"
                value="{{ old('elementary_school') }}">
              <label>Elementary School</label>
            </div>
            <div class="col-md-6 form-floating">
              <input type="number" name="elementary_year_graduated" class="form-control" placeholder="Year Graduated"
                value="{{ old('elementary_year_graduated') }}">
              <label>Year Graduated</label>
            </div>
            <div class="col-md-6 form-floating">
              <input type="text" name="high_school" class="form-control" placeholder="High School"
                value="{{ old('high_school') }}">
              <label>High School</label>
            </div>
            <div class="col-md-6 form-floating">
              <input type="number" name="high_school_year_graduated" class="form-control" placeholder="Year Graduated"
                value="{{ old('high_school_year_graduated') }}">
              <label>Year Graduated</label>
            </div>
            <div class="col-md-6 form-floating">
              <input type="text" name="college" class="form-control" placeholder="College" value="{{ old('college') }}">
              <label>College</label>
            </div>
            <div class="col-md-6 form-floating">
              <input type="number" name="college_year_graduated" class="form-control" placeholder="Year Graduated"
                value="{{ old('college_year_graduated') }}">
              <label>Year Graduated</label>
            </div>
            <div class="col-md-6 form-floating">
              <input type="text" name="degree_received" class="form-control" placeholder="Degree Received"
                value="{{ old('degree_received') }}">
              <label>Degree Received</label>
            </div>
            <div class="col-12 form-floating">
              <textarea name="special_skills" class="form-control" placeholder="Special Skills"
                style="height:120px">{{ old('special_skills') }}</textarea>
              <label>Special Skills</label>
            </div>
          </div>
        </div>
      </section>

      {{-- EMPLOYMENT HISTORY --}}
      <section id="tab-employment" class="tab-panel" hidden>
        <div class="panel">
          <div class="form-grid">
            <div class="span-12">
              <h6 class="fw-bold">💼 Employment Record</h6>
            </div>

            {{-- Row 1 --}}
            <div class="form-floating span-5">
              <input type="text" name="emp1_company" class="form-control" placeholder="Company"
                value="{{ old('emp1_company') }}">
              <label>Company</label>
            </div>
            <div class="form-floating span-3">
              <input type="text" name="emp1_position" class="form-control" placeholder="Position"
                value="{{ old('emp1_position') }}">
              <label>Position</label>
            </div>
            <div class="form-floating span-2">
              <input type="date" name="emp1_from" class="form-control" placeholder="From" value="{{ old('emp1_from') }}">
              <label>From</label>
            </div>
            <div class="form-floating span-2">
              <input type="date" name="emp1_to" class="form-control" placeholder="To" value="{{ old('emp1_to') }}">
              <label>To</label>
            </div>

            {{-- Row 2 --}}
            <div class="form-floating span-5">
              <input type="text" name="emp2_company" class="form-control" placeholder="Company"
                value="{{ old('emp2_company') }}">
              <label>Company</label>
            </div>
            <div class="form-floating span-3">
              <input type="text" name="emp2_position" class="form-control" placeholder="Position"
                value="{{ old('emp2_position') }}">
              <label>Position</label>
            </div>
            <div class="form-floating span-2">
              <input type="date" name="emp2_from" class="form-control" placeholder="From" value="{{ old('emp2_from') }}">
              <label>From</label>
            </div>
            <div class="form-floating span-2">
              <input type="date" name="emp2_to" class="form-control" placeholder="To" value="{{ old('emp2_to') }}">
              <label>To</label>
            </div>
          </div>
        </div>
      </section>

      {{-- CHARACTER REFERENCES --}}
      <section id="tab-refs" class="tab-panel" hidden>
        <div class="panel">
          <div class="form-grid">
            <div class="span-12">
              <h6 class="fw-bold">📝 Character References</h6>
            </div>

            {{-- Row 1 --}}
            <div class="form-floating span-4">
              <input type="text" name="char1_name" class="form-control" placeholder="Name"
                value="{{ old('char1_name') }}">
              <label>Name</label>
            </div>
            <div class="form-floating span-3">
              <input type="text" name="char1_position" class="form-control" placeholder="Position"
                value="{{ old('char1_position') }}">
              <label>Position</label>
            </div>
            <div class="form-floating span-3">
              <input type="text" name="char1_company" class="form-control" placeholder="Company"
                value="{{ old('char1_company') }}">
              <label>Company</label>
            </div>
            <div class="form-floating span-2">
              <input type="text" name="char1_contact" class="form-control" placeholder="Contact"
                value="{{ old('char1_contact') }}">
              <label>Contact</label>
            </div>

            {{-- Row 2 --}}
            <div class="form-floating span-4">
              <input type="text" name="char2_name" class="form-control" placeholder="Name"
                value="{{ old('char2_name') }}">
              <label>Name</label>
            </div>
            <div class="form-floating span-3">
              <input type="text" name="char2_position" class="form-control" placeholder="Position"
                value="{{ old('char2_position') }}">
              <label>Position</label>
            </div>
            <div class="form-floating span-3">
              <input type="text" name="char2_company" class="form-control" placeholder="Company"
                value="{{ old('char2_company') }}">
              <label>Company</label>
            </div>
            <div class="form-floating span-2">
              <input type="text" name="char2_contact" class="form-control" placeholder="Contact"
                value="{{ old('char2_contact') }}">
              <label>Contact</label>
            </div>
          </div>
        </div>
      </section>

      {{-- CERTIFICATES & DOCS --}}
      <section id="tab-docs" class="tab-panel" hidden>
        <div class="panel">
          <div class="row g-12">
            <div class="col-12">
              <h6 class="fw-bold">📑 Certificates & Docs</h6>
            </div>

            <div class="col-md-6">
              <label class="form-label">Resume</label>
              <input type="file" name="resume_file" class="form-control @error('resume_file') is-invalid @enderror"
                accept=".pdf,.doc,.docx,image/*">
              @error('resume_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
              <label class="form-label">MDR – PhilHealth</label>
              <input type="file" name="mdr_philhealth_file"
                class="form-control @error('mdr_philhealth_file') is-invalid @enderror" accept=".pdf,image/*">
              @error('mdr_philhealth_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
              <label class="form-label">MDR – SSS</label>
              <input type="file" name="mdr_sss_file" class="form-control @error('mdr_sss_file') is-invalid @enderror"
                accept=".pdf,image/*">
              @error('mdr_sss_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
              <label class="form-label">MDR – Pag-IBIG</label>
              <input type="file" name="mdr_pagibig_file"
                class="form-control @error('mdr_pagibig_file') is-invalid @enderror" accept=".pdf,image/*">
              @error('mdr_pagibig_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
              <label class="form-label">Medical Documents</label>
              <input type="file" name="medical_documents[]" multiple
                class="form-control @error('medical_documents.*') is-invalid @enderror" accept=".pdf,image/*">
              @error('medical_documents.*')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>
        </div>
      </section>

      <div class="form-actions mt-4 p-3 bg-light border-top d-flex justify-content-end gap-2 sticky-bottom">

        <!-- Cancel -->
        <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">
          <i class="bi bi-x-circle"></i> Cancel
        </a>

        <!-- NEXT (visible except last tab) -->
        <button type="button" id="nextBtn" class="btn btn-primary">
          Next <i class="bi bi-arrow-right-short ms-1"></i>
        </button>

        <!-- SAVE (only visible on last tab) -->
        <button type="submit" id="saveBtn" class="btn btn-success d-none">
          <i class="bi bi-save2 me-1"></i> Save
        </button>

      </div>


@endsection

    @push('modals')
      {{-- CAMERA MODAL --}}
      <div class="modal fade" id="cameraModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title"><i class="bi bi-camera-video"></i> Capture Profile Picture</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="ratio ratio-4x3 face-stage">
                <video id="ppVideo" autoplay muted playsinline style="width:100%;height:100%;object-fit:cover;"></video>
              </div>
              <div class="form-text mt-2 help-minor" id="ppStatus">Allow camera access, then click Capture.</div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" id="ppStop"><i class="bi bi-stop-circle"></i>
                Stop</button>
              <button type="button" class="btn btn-primary" id="ppSnap"><i class="bi bi-record-circle"></i>
                Capture</button>
            </div>
          </div>
        </div>
      </div>
    @endpush

    @push('scripts')
      <script defer src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.min.js"></script>
      <script>

        /* ===============================================================
           TAB SWITCHING VALIDATION (UPDATED)
        ================================================================ */
        document.addEventListener("DOMContentLoaded", function () {

          const tabs = [...document.querySelectorAll("#tabs a")];
          const panels = [...document.querySelectorAll(".tab-panel")];

          const nextBtn = document.getElementById("nextBtn");
          const saveBtn = document.getElementById("saveBtn");

          /* ---------------------------
             Validate required fields
          --------------------------- */
          function validateCurrentTab() {
            const activeIndex = tabs.findIndex(t => t.classList.contains("active"));
            const currentPanel = panels[activeIndex];
            const requiredFields = [...currentPanel.querySelectorAll("[required]")];

            let invalidFields = requiredFields.filter(f => !f.value.trim());

            // reset
            requiredFields.forEach(f => f.classList.remove("is-invalid"));

            if (invalidFields.length) {
              invalidFields.forEach(f => f.classList.add("is-invalid"));

              // scroll to first invalid field
              invalidFields[0].scrollIntoView({ behavior: "smooth", block: "center" });

              alert("Please complete all required fields before proceeding.");

              return false; // ❌ stop switching
            }

            return true; // ✔ ok to switch
          }

          /* ---------------------------
             Update buttons visibility
          --------------------------- */
          function updateButtons() {
            const activeIndex = tabs.findIndex(t => t.classList.contains("active"));

            if (activeIndex === tabs.length - 1) {
              nextBtn.classList.add("d-none");
              saveBtn.classList.remove("d-none");
            } else {
              nextBtn.classList.remove("d-none");
              saveBtn.classList.add("d-none");
            }
          }

          /* ---------------------------
             On NEXT button click
          --------------------------- */
          nextBtn.addEventListener("click", function () {
            if (!validateCurrentTab()) return;

            const activeIndex = tabs.findIndex(t => t.classList.contains("active"));
            if (activeIndex < tabs.length - 1) {
              const nextTab = tabs[activeIndex + 1];

              tabs.forEach(t => t.classList.remove("active"));
              nextTab.classList.add("active");

              const nextPanelId = nextTab.getAttribute("href");
              panels.forEach(p => p.hidden = ("#" + p.id) !== nextPanelId);

              updateButtons();
            }
          });

          /* ---------------------------
             Manual Tab Click
          --------------------------- */
          tabs.forEach(tab => {
            tab.addEventListener("click", function (e) {

              // prevent skipping tabs if required fields not filled
              if (!validateCurrentTab()) {
                e.preventDefault();
                return;
              }

              // allow switching
              tabs.forEach(t => t.classList.remove("active"));
              tab.classList.add("active");

              const id = tab.getAttribute("href");
              panels.forEach(p => p.hidden = ("#" + p.id) !== id);

              updateButtons();
            });
          });

          updateButtons();
        });


        /* ===============================================================
           PSGC Loader + DOB + Auto Password + Other Existing Scripts
           (UNCHANGED FROM YOUR VERSION)
        ================================================================ */

        /* ---- Tabs base behavior (still needed for page load) ---- */
        const tabsNav = document.getElementById('tabs');
        const panels = [...document.querySelectorAll('.tab-panel')];
        tabsNav.addEventListener('click', e => {
          const a = e.target.closest('a'); if (!a) return; e.preventDefault();
        });
        panels.forEach(p => p.hidden = (p.id !== 'tab-personal'));

        /* ---- PSGC loader (unchanged) ---- */
        const PSGC_BASE = @json(asset('psgc'));
        const CITY_INDEX = {
          'Batangas': `${PSGC_BASE}/Batangas/index.json`,
          'Cavite': `${PSGC_BASE}/Cavite/index.json`,
          'Laguna': `${PSGC_BASE}/Laguna/index.json`,
          'Rizal': `${PSGC_BASE}/Rizal/index.json`,
          'Quezon': `${PSGC_BASE}/Quezon/index.json`,
        };

        const provinceSel = document.getElementById('current_province');
        const citySel = document.getElementById('current_city_select');
        const cityHidden = document.getElementById('current_city');
        const brgySel = document.getElementById('current_barangay_select');
        const brgyHidden = document.getElementById('current_barangay');
        const zipInput = document.getElementById('current_postal_code');

        function clearOptions(sel, ph) {
          sel.innerHTML = '';
          const o = document.createElement('option');
          o.value = ''; o.disabled = true; o.selected = true; o.textContent = ph;
          sel.appendChild(o);
        }

        async function loadJson(url) {
          const r = await fetch(url, { cache: 'no-cache' });
          if (!r.ok) throw new Error('HTTP ' + r.status);
          return r.json();
        }

        async function loadProvinceIndex(province) {
          clearOptions(citySel, 'Select City / Municipality');
          clearOptions(brgySel, 'Select Barangay');
          zipInput.value = ''; zipInput.readOnly = false; zipInput.classList.remove('autofilled');
          const url = CITY_INDEX[province]; if (!url) return;
          const data = await loadJson(url);
          (data.cities || []).forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.city;
            opt.dataset.slug = c.slug;
            opt.textContent = c.city;
            citySel.appendChild(opt);
          });
        }

        async function loadCityBarangays(province, city) {
          clearOptions(brgySel, 'Select Barangay');
          zipInput.value = ''; zipInput.readOnly = false; zipInput.classList.remove('autofilled');

          const slug = citySel.selectedOptions[0]?.dataset.slug;
          if (!slug) return;

          const data = await loadJson(`${PSGC_BASE}/${province}/${slug}.json`);

          (data.barangays || []).forEach(b => {
            const opt = document.createElement('option');
            opt.value = b.name;
            opt.textContent = b.name;
            if (b.zip) opt.dataset.zip = b.zip;
            brgySel.appendChild(opt);
          });

          brgySel.addEventListener('change', () => {
            brgyHidden.value = brgySel.value;
            const sel = brgySel.selectedOptions[0];
            if (sel?.dataset.zip) {
              zipInput.value = sel.dataset.zip;
              zipInput.readOnly = true;
              zipInput.classList.add('autofilled');
            } else {
              zipInput.value = '';
              zipInput.readOnly = false;
              zipInput.classList.remove('autofilled');
            }
          });
        }

        provinceSel.addEventListener('change', () => { loadProvinceIndex(provinceSel.value).catch(console.error); });
        citySel.addEventListener('change', () => { cityHidden.value = citySel.value; loadCityBarangays(provinceSel.value, citySel.value).catch(console.error); });
        brgySel.addEventListener('change', () => { brgyHidden.value = brgySel.value; });

        (function initOld() {
          const oldProv = @json(old('current_province')), oldCity = @json(old('current_city')), oldBrgy = @json(old('current_barangay'));
          if (oldProv) {
            provinceSel.value = oldProv;
            loadProvinceIndex(oldProv).then(() => {
              [...citySel.options].forEach(o => { if (o.value === oldCity) o.selected = true; });
              cityHidden.value = oldCity;
              return loadCityBarangays(oldProv, oldCity);
            }).then(() => {
              [...brgySel.options].forEach(o => { if (o.value === oldBrgy) o.selected = true; });
              brgyHidden.value = oldBrgy;
            }).catch(console.error);
          }
        })();



        /* ---- 18+ guard ---- */
        const dobEl = document.getElementById('dob');
        (function setDobMax18() { const t = new Date(); const max = new Date(t.getFullYear() - 18, t.getMonth(), t.getDate()); dobEl.setAttribute('max', max.toISOString().slice(0, 10)); })();
        function isAdult(v) { const d = new Date(v); if (isNaN(d.getTime())) return false; const t = new Date(); let age = t.getFullYear() - d.getFullYear(); const m = t.getMonth() - d.getMonth(); if (m < 0 || (m === 0 && t.getDate() < d.getDate())) age--; return age >= 18; }
        document.getElementById('empCreateForm').addEventListener('submit', (e) => {
          if (!isAdult(dobEl.value)) {
            e.preventDefault(); dobEl.setCustomValidity('Employee must be at least 18 years old.'); dobEl.reportValidity();
            tabsNav.querySelectorAll('a').forEach(x => x.classList.remove('active'));
            tabsNav.querySelector('a[href="#tab-personal"]').classList.add('active');
            panels.forEach(p => p.hidden = (p.id !== 'tab-personal'));
          } else dobEl.setCustomValidity('');
        });

        /* ---- Auto compute end date for probationary employment ---- */
        const empTypeEl = document.getElementById('employment_type');
        const startDateEl = document.getElementById('employment_start_date');
        const endDateEl = document.getElementById('employment_end_date');
        const endDateLabel = document.getElementById('endDateLabel');

        function computeEndDate() {
          const empType = (empTypeEl.value || '').toLowerCase();
          const startVal = startDateEl.value;

          // When employment type is probationary → auto add 6 months
          if (empType === 'probationary') {
            if (startVal) {
              const start = new Date(startVal);
              if (!isNaN(start.getTime())) {
                const end = new Date(start);
                end.setMonth(end.getMonth() + 6);

                // Handle month overflow (e.g., Jan 31 + 6 months)
                if (end.getDate() !== start.getDate()) {
                  end.setDate(0);
                }

                // Format YYYY-MM-DD
                endDateEl.value = end.toISOString().split('T')[0];
              }
            }
            endDateEl.readOnly = true;
            endDateEl.classList.add('bg-light');
            endDateLabel.textContent = 'End Date (auto 6 months)';
          } else {
            // Other employment types → manual input
            endDateEl.readOnly = false;
            endDateEl.classList.remove('bg-light');
            endDateLabel.textContent = 'End Date';
            if (empType === 'casual' || empType === 'project' || empType === 'fixed-term' || empType === 'seasonal') {
              endDateLabel.textContent = 'End Date *';
            }
          }
        }

        // Trigger computation when fields change
        empTypeEl.addEventListener('change', computeEndDate);
        startDateEl.addEventListener('change', computeEndDate);

        // Run once on page load (for old values)
        document.addEventListener('DOMContentLoaded', computeEndDate);


        /* ---- Auto password ---- */
        const lastNameEl = document.getElementById('last_name'), pwdEl = document.getElementById('password'), pwd2El = document.getElementById('password_confirm'), autoPwd = document.getElementById('autoPwd');
        const nameNoSpaces = s => (s || '').trim().replace(/\s+/g, ''); const birthYear = () => { const v = dobEl.value; if (!v) return ''; const d = new Date(v); return isNaN(d.getTime()) ? '' : String(d.getFullYear()); };
        function makePassword() { const ln = nameNoSpaces(lastNameEl.value), yr = birthYear(); return (ln && yr) ? `${ln}${yr}` : ''; }
        function applyAutoPassword() { if (autoPwd.checked) { const gen = makePassword(); pwdEl.value = gen; pwd2El.value = gen; pwdEl.readOnly = true; pwd2El.readOnly = true; } else { pwdEl.readOnly = false; pwd2El.readOnly = false; } }
        [lastNameEl, dobEl].forEach(el => el.addEventListener('input', () => { if (autoPwd.checked) applyAutoPassword(); })); autoPwd.addEventListener('change', applyAutoPassword); document.addEventListener('DOMContentLoaded', applyAutoPassword);

        /* ---- Face capture overlay ---- */
        const MODEL_URI = "{{ asset('face-models') }}";
        const video = document.getElementById('video'); const stage = document.getElementById('stage'); const btnStart = document.getElementById('btnStart'); const btnCapture = document.getElementById('btnCapture'); const camStatus = document.getElementById('camStatus'); const prevCanvas = document.getElementById('facePreview'); let modelsLoaded = false, boxEl = null;
        function ensureBox() { if (boxEl) return; boxEl = document.createElement('div'); Object.assign(boxEl.style, { position: 'absolute', border: '2px solid #60a5fa', borderRadius: '10px', pointerEvents: 'none' }); stage.appendChild(boxEl); }
        function hideBox() { if (boxEl) boxEl.style.display = 'none'; }
        function showBox(x, y, w, h) { ensureBox(); Object.assign(boxEl.style, { display: 'block', left: x + 'px', top: y + 'px', width: w + 'px', height: h + 'px' }); }
        async function loadModels() { if (modelsLoaded) return; await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URI); await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URI); await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URI); modelsLoaded = true; }
        btnStart?.addEventListener('click', async () => { try { await loadModels(); const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false }); video.srcObject = stream; camStatus.textContent = 'Camera ready. Keep your face within the box; press Capture.'; btnCapture.disabled = false; const opts = new faceapi.TinyFaceDetectorOptions({ inputSize: 544, scoreThreshold: .35 }); const loop = async () => { if (!video.srcObject) return; const r = await faceapi.detectSingleFace(video, opts); if (!r) { hideBox(); requestAnimationFrame(loop); return; } const vw = video.videoWidth, vh = video.videoHeight; const rw = stage.clientWidth, rh = stage.clientHeight; const sx = rw / vw, sy = rh / vh; const b = r.box; showBox(b.x * sx, b.y * sy, b.width * sx, b.height * sy); requestAnimationFrame(loop); }; requestAnimationFrame(loop); } catch (e) { camStatus.textContent = 'Cannot access camera: ' + e.message; } });
        btnCapture?.addEventListener('click', async () => { try { await loadModels(); if (!video.srcObject) { camStatus.textContent = 'Start the camera first.'; return; } const det = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 640, scoreThreshold: .32 })).withFaceLandmarks().withFaceDescriptor(); if (!det) { camStatus.textContent = 'No face detected. Move closer and face the camera.'; return; } const c = prevCanvas, ctx = c.getContext('2d'); const vw = video.videoWidth, vh = video.videoHeight; c.width = 600; c.height = 800; const dstR = c.width / c.height, srcR = vw / vh; let sx = 0, sy = 0, sw = vw, sh = vh; if (srcR > dstR) { sw = vh * dstR; sx = (vw - sw) / 2; } else { sh = vw / dstR; sy = (vh - sh) / 2; } ctx.fillStyle = '#f6f8fe'; ctx.fillRect(0, 0, c.width, c.height); ctx.drawImage(video, sx, sy, sw, sh, 0, 0, c.width, c.height); document.getElementById('face_descriptor').value = JSON.stringify(Array.from(det.descriptor)); document.getElementById('face_image_base64').value = c.toDataURL('image/png'); camStatus.textContent = 'Captured! The face template will be saved with this employee.'; } catch (e) { camStatus.textContent = 'Capture error: ' + e.message; } });

        /* ---- Spouse show/hide ---- */
        const civilSel = document.getElementById('civil_status'); const spouseWrap = document.getElementById('spouseRow');
        function toggleSpouseRow() { const married = (civilSel?.value || '').toLowerCase() === 'married'; spouseWrap.hidden = !married; document.getElementById('spouse_name')?.toggleAttribute('required', married); document.getElementById('spouse_contact')?.toggleAttribute('required', married); }
        civilSel?.addEventListener('change', toggleSpouseRow); document.addEventListener('DOMContentLoaded', toggleSpouseRow);

        /* ---- Camera modal for profile picture ---- */
        const cameraModal = document.getElementById('cameraModal'); const ppHidden = document.getElementById('profile_picture_camera'); const fileInput = document.getElementById('profile_picture_file'); const ppVideo = document.getElementById('ppVideo'); const ppSnap = document.getElementById('ppSnap'); const ppStop = document.getElementById('ppStop'); const ppStatus = document.getElementById('ppStatus'); let ppStream = null;
        function stopPP() { if (ppStream) { ppStream.getTracks().forEach(t => t.stop()); ppStream = null; } if (ppVideo) ppVideo.srcObject = null; }
        cameraModal.addEventListener('shown.bs.modal', async () => { try { ppStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false }); ppVideo.srcObject = ppStream; ppStatus.textContent = 'Camera ready. Click Capture.'; } catch (e) { ppStatus.textContent = 'Cannot access camera: ' + e.message; } });
        cameraModal.addEventListener('hide.bs.modal', stopPP);
        ppStop.addEventListener('click', stopPP);
        ppSnap.addEventListener('click', () => { if (!ppStream) { ppStatus.textContent = 'Open the modal to start camera.'; return; } const canvas = document.createElement('canvas'); const v = ppVideo; const W = 600, H = 800; canvas.width = W; canvas.height = H; const ctx = canvas.getContext('2d'); const sw = v.videoWidth, sh = v.videoHeight, dstR = W / H, srcR = sw / sh; let sx = 0, sy = 0, sW = sw, sH = sh; if (srcR > dstR) { sW = sh * dstR; sx = (sw - sW) / 2; } else { sH = sw / dstR; sy = (sh - sH) / 2; } ctx.drawImage(v, sx, sy, sW, sH, 0, 0, W, H); const dataUrl = canvas.toDataURL('image/png'); ppHidden.value = dataUrl; if (fileInput) fileInput.value = ''; bootstrap.Modal.getInstance(cameraModal)?.hide(); });

        /* ---- Client-side upload diagnostics (NEW) ---- */
        (function () {
          const form = document.getElementById('empCreateForm'); if (!form) return;
          const FIELDS_SINGLE = ['profile_picture', 'resume_file', 'mdr_philhealth_file', 'mdr_sss_file', 'mdr_pagibig_file'];
          const FIELD_MULTI = 'medical_documents[]';
          const phpMaxFile = "{{ (string) ($phpMaxFile ?? '') }}";
          const phpMaxPost = "{{ (string) ($phpMaxPost ?? '') }}";
          const toBytes = (s) => { if (!s) return Infinity; const m = String(s).trim().match(/^(\d+(?:\.\d+)?)([KMG])?$/i); if (!m) return Infinity; let v = parseFloat(m[1]); const u = (m[2] || '').toUpperCase(); if (u === 'K') v *= 1024; if (u === 'M') v *= 1024 * 1024; if (u === 'G') v *= 1024 * 1024 * 1024; return Math.floor(v); };
          const limitLaravel = 5 * 1024 * 1024; // validator max:5120KB
          const limitPhpFile = toBytes(phpMaxFile);
          const limitPhpPost = toBytes(phpMaxPost);
          const hardPerFile = Math.min(limitLaravel, limitPhpFile);
          const warnEl = document.getElementById('cl-upload-warning');
          const sumWrap = document.getElementById('cl-file-summary');
          const listEl = document.getElementById('cl-file-list');
          const human = (n) => n >= 1073741824 ? (n / 1073741824).toFixed(2) + ' GB' : n >= 1048576 ? (n / 1048576).toFixed(2) + ' MB' : n >= 1024 ? (n / 1024).toFixed(2) + ' KB' : n + ' B';
          function collectFiles() {
            const files = []; FIELDS_SINGLE.forEach(name => { const el = form.querySelector(`input[type="file"][name="${name}"]`); if (el?.files?.length) files.push(...el.files); });
            const multi = form.querySelector(`input[type="file"][name="${FIELD_MULTI}"]`); if (multi?.files?.length) files.push(...multi.files);
            return files;
          }
          function refreshSummary() {
            const files = collectFiles(); if (!files.length) { sumWrap.classList.add('d-none'); listEl.innerHTML = ''; return; }
            sumWrap.classList.remove('d-none'); listEl.innerHTML = ''; files.forEach(f => { const li = document.createElement('li'); li.textContent = `${f.name} — ${human(f.size)} (${f.type || 'type/unknown'})`; if (f.size > hardPerFile) { li.classList.add('text-danger'); li.append('  ✖ exceeds per-file limit'); } listEl.appendChild(li); });
          }
          form.addEventListener('change', e => { if (e.target.matches('input[type="file"]')) refreshSummary(); });
          form.addEventListener('submit', e => {
            warnEl?.classList.add('d-none');
            const files = collectFiles();
            const tooBig = files.find(f => f.size > hardPerFile);
            if (tooBig) { e.preventDefault(); warnEl.textContent = `“${tooBig.name}” is ${human(tooBig.size)} but the limit is ${human(hardPerFile)}. Replace the file and try again.`; warnEl.classList.remove('d-none'); refreshSummary(); return; }
            const total = files.reduce((a, f) => a + f.size, 0);
            if (total > limitPhpPost && Number.isFinite(limitPhpPost)) { e.preventDefault(); warnEl.innerHTML = `Total size of selected files is ${human(total)}, which likely exceeds <code>post_max_size</code> ({{ $phpMaxPost }}). Increase it in php.ini or upload fewer/smaller files.`; warnEl.classList.remove('d-none'); refreshSummary(); return; }
            if (!files.length) { warnEl.textContent = 'No documents selected. That’s fine, but nothing will be saved in the document fields.'; warnEl.classList.remove('d-none'); }
          });
          refreshSummary();


        })();

        /* ---------------------------------------------------------------
           DYNAMIC NEXT / SAVE BUTTON FOR TAB PROGRESS
        ---------------------------------------------------------------- */
        document.addEventListener('DOMContentLoaded', function () {

          const tabs = [...document.querySelectorAll('#tabs a')];
          const panels = [...document.querySelectorAll('.tab-panel')];

          const nextBtn = document.getElementById('nextBtn');
          const saveBtn = document.getElementById('saveBtn');

          function updateButtons() {
            const activeIndex = tabs.findIndex(t => t.classList.contains('active'));

            // If last tab → show Save button
            if (activeIndex === tabs.length - 1) {
              nextBtn.classList.add('d-none');
              saveBtn.classList.remove('d-none');
            } else {
              nextBtn.classList.remove('d-none');
              saveBtn.classList.add('d-none');
            }
          }

          // NEXT button → switch to next tab
          nextBtn.addEventListener('click', function () {
            const activeIndex = tabs.findIndex(t => t.classList.contains('active'));

            if (activeIndex < tabs.length - 1) {
              const nextTab = tabs[activeIndex + 1];

              // Switch active tab
              tabs.forEach(t => t.classList.remove('active'));
              nextTab.classList.add('active');

              // Switch visible panel
              const nextPanelId = nextTab.getAttribute('href');
              panels.forEach(p => p.hidden = ('#' + p.id) !== nextPanelId);

              updateButtons();
            }
          });

          // When manually clicking tabs
          tabs.forEach(tab => {
            tab.addEventListener('click', function () {
              setTimeout(updateButtons, 50);
            });
          });

          updateButtons();
        });

      </script>
    @endpush