@extends('layouts.app')
@section('page_title', 'My HR Documents')

@section('content')
<div class="container py-4" style="max-width:780px">
  <div class="text-center mb-4">
    <h3 class="fw-bold mb-1">My HR Documents</h3>
    <p class="text-muted mb-0">Download your official records below.</p>
  </div>

  <div class="row g-4">
    <!-- Certificate of Employment -->
    <div class="col-md-6">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-body text-center">
          <i class="bi bi-file-earmark-text fs-1 text-primary mb-2"></i>
          <h6 class="fw-semibold mb-1">Certificate of Employment</h6>
          <p class="small text-muted mb-3">Generate and download your latest CoE PDF.</p>
          <a href="{{ route('mydocs.coe') }}" class="btn btn-primary w-100">
            <i class="bi bi-download me-1"></i>Download CoE
          </a>
        </div>
      </div>
    </div>

    <!-- Employee Information Sheet -->
    <div class="col-md-6">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-body text-center">
          <i class="bi bi-person-badge fs-1 text-success mb-2"></i>
          <h6 class="fw-semibold mb-1">Employee Information Sheet</h6>
          <p class="small text-muted mb-3">Get a summary of your employee profile.</p>
          <a href="{{ route('mydocs.eis') }}" class="btn btn-success w-100">
            <i class="bi bi-download me-1"></i>Download EIS
          </a>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
