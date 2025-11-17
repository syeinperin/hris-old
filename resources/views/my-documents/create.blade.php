@extends('layouts.app')
@section('page_title','Upload Document')

@section('content')
<div class="container" style="max-width:760px;">
  <h3 class="mb-3">Upload Document</h3>
  <div class="card">
    <div class="card-body">
      <form action="{{ route('mydocs.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="mb-3">
          <label class="form-label">Title</label>
          <input type="text" name="title" class="form-control" required value="{{ old('title') }}">
        </div>

        <div class="mb-3">
          <label class="form-label">Type</label>
          <select name="doc_type" class="form-select" required>
            <option value="resume">Resume</option>
            <optgroup label="MDR">
              <option value="mdr_philhealth">MDR – PhilHealth</option>
              <option value="mdr_sss">MDR – SSS</option>
              <option value="mdr_pagibig">MDR – Pag-IBIG</option>
            </optgroup>
            <option value="medical">Medical</option>
            <option value="other">Other</option>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">File</label>
          <input type="file" name="file" class="form-control" required>
          <small class="text-muted">PDF, DOC, DOCX, JPG, PNG (max 10MB)</small>
        </div>

        <div class="mb-3">
          <label class="form-label">Notes (optional)</label>
          <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
        </div>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Visibility</label>
            <select name="visibility" class="form-select">
              <option value="employee" selected>Employee only</option>
              <option value="private_employee">Private (me only)</option>
              <option value="hr">HR</option>
              <option value="supervisor">Supervisor</option>
              <option value="hr_supervisor">HR & Supervisor</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Expires At (optional)</label>
            <input type="date" name="expires_at" class="form-control" value="{{ old('expires_at') }}">
          </div>
        </div>

        <div class="mt-4 d-flex gap-2">
          <a href="{{ route('mydocs.index') }}" class="btn btn-light">Cancel</a>
          <button class="btn btn-primary">
            <i class="bi bi-upload me-1"></i> Upload
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
