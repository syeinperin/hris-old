@extends('layouts.app')
<<<<<<< HEAD
@section('page_title', 'View Document')

@section('content')
<div class="container" style="max-width:960px;">
  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-0">{{ $document->title ?? 'Untitled Document' }}</h3>
      <small class="text-muted">
        Type:
        <span class="text-capitalize">{{ $document->doc_type ?? '—' }}</span> ·
        Version: v{{ $document->version ?? '1' }} ·
        Uploaded
        {{ optional($document->created_at)->diffForHumans() ?? '—' }}
      </small>
    </div>
    <div class="d-flex gap-2">
      @if($document->file_path)
        <a class="btn btn-outline-secondary" href="{{ route('mydocs.download', $document) }}">
          <i class="bi bi-download me-1"></i>Download
        </a>
      @endif
      <a class="btn btn-outline-primary" href="{{ route('mydocs.edit', $document) }}">
        <i class="bi bi-pencil-square me-1"></i>Edit
      </a>
=======
@section('page_title','View Document')

@section('content')
<div class="container" style="max-width:960px;">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-0">{{ $document->title }}</h3>
      <small class="text-muted">
        Type: <span class="text-capitalize">{{ $document->doc_type }}</span> ·
        Version: v{{ $document->version }} ·
        Uploaded {{ $document->created_at->diffForHumans() }}
      </small>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="{{ route('mydocs.download',$document) }}"><i class="bi bi-download me-1"></i>Download</a>
      <a class="btn btn-outline-primary" href="{{ route('mydocs.edit',$document) }}"><i class="bi bi-pencil-square me-1"></i>Edit</a>
>>>>>>> 3c9cdd43629a382660afb438d823e144bd39036a
      <a class="btn btn-light" href="{{ route('mydocs.index') }}">Back</a>
    </div>
  </div>

<<<<<<< HEAD
  <!-- Body -->
  <div class="row g-3">
    <!-- Left: Preview -->
    <div class="col-md-8">
      <div class="card">
        <div class="card-body" style="min-height:480px;">
          @php
            $ext = strtolower(pathinfo($document->file_path ?? '', PATHINFO_EXTENSION));
          @endphp

          @if($document->file_path && in_array($ext, ['pdf']))
            <iframe
              src="{{ route('public.files', $document->file_path) }}"
              width="100%" height="600" style="border:0;"
              title="PDF Preview"
            ></iframe>

          @elseif($document->file_path && in_array($ext, ['jpg','jpeg','png']))
            <img
              src="{{ route('public.files', $document->file_path) }}"
              class="img-fluid rounded"
              alt="Document Preview"
            >

          @elseif($document->file_path)
=======
  <div class="row g-3">
    <div class="col-md-8">
      <div class="card">
        <div class="card-body" style="min-height:480px">
          @php $ext = strtolower(pathinfo($document->file_path, PATHINFO_EXTENSION)); @endphp

          @if(in_array($ext,['pdf']))
            <iframe src="{{ route('public.files',$document->file_path) }}" width="100%" height="600" style="border:0;"></iframe>
          @elseif(in_array($ext,['jpg','jpeg','png']))
            <img src="{{ route('public.files',$document->file_path) }}" class="img-fluid rounded" alt="Document Preview">
          @else
>>>>>>> 3c9cdd43629a382660afb438d823e144bd39036a
            <div class="text-center text-muted py-5">
              <i class="bi bi-file-earmark-text fs-1 d-block mb-2"></i>
              Preview not available. Use Download instead.
            </div>
<<<<<<< HEAD
          @else
            <div class="text-center text-muted py-5">
              <i class="bi bi-exclamation-circle fs-1 d-block mb-2"></i>
              No file available for this document.
            </div>
=======
>>>>>>> 3c9cdd43629a382660afb438d823e144bd39036a
          @endif
        </div>
      </div>
    </div>

<<<<<<< HEAD
    <!-- Right: Details -->
=======
>>>>>>> 3c9cdd43629a382660afb438d823e144bd39036a
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <h6 class="fw-semibold mb-2">Details</h6>
          <dl class="row mb-0 small">
            <dt class="col-5">Status</dt>
            <dd class="col-7">
<<<<<<< HEAD
              @php
                $status = $document->status ?? 'pending';
                $badgeClass = match($status) {
                    'approved' => 'success',
                    'rejected' => 'danger',
                    default => 'secondary'
                };
              @endphp
              <span class="badge text-bg-{{ $badgeClass }}">
                {{ ucfirst($status) }}
=======
              <span class="badge text-bg-{{ $document->status==='approved'?'success':($document->status==='rejected'?'danger':'secondary') }}">
                {{ ucfirst($document->status) }}
>>>>>>> 3c9cdd43629a382660afb438d823e144bd39036a
              </span>
            </dd>

            <dt class="col-5">Visibility</dt>
<<<<<<< HEAD
            <dd class="col-7">
              {{ $document->visibility ? str_replace('_',' ',ucfirst($document->visibility)) : '—' }}
            </dd>
=======
            <dd class="col-7">{{ str_replace('_',' ',ucfirst($document->visibility)) }}</dd>
>>>>>>> 3c9cdd43629a382660afb438d823e144bd39036a

            <dt class="col-5">Expires</dt>
            <dd class="col-7">
              @if($document->expires_at)
<<<<<<< HEAD
                <span class="{{ $document->isExpired() ? 'text-danger fw-semibold' : '' }}">
                  {{ $document->expires_at->format('M d, Y') }}
                </span>
              @else
                —
              @endif
=======
                <span class="{{ $document->isExpired() ? 'text-danger' : '' }}">
                  {{ $document->expires_at->format('M d, Y') }}
                </span>
              @else  @endif
>>>>>>> 3c9cdd43629a382660afb438d823e144bd39036a
            </dd>

            <dt class="col-5">Uploaded by</dt>
            <dd class="col-7">{{ $document->uploader?->name ?? '—' }}</dd>

            <dt class="col-5">Notes</dt>
<<<<<<< HEAD
            <dd class="col-7 text-break">{{ $document->notes ?: '—' }}</dd>

            <dt class="col-5">Uploaded on</dt>
            <dd class="col-7">
              {{ optional($document->created_at)->format('M d, Y h:i A') ?? '—' }}
            </dd>

            <dt class="col-5">Last Updated</dt>
            <dd class="col-7">
              {{ optional($document->updated_at)->format('M d, Y h:i A') ?? '—' }}
            </dd>
=======
            <dd class="col-7">{{ $document->notes ?: '—' }}</dd>
>>>>>>> 3c9cdd43629a382660afb438d823e144bd39036a
          </dl>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
