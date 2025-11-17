@extends('layouts.app')
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
      <a class="btn btn-light" href="{{ route('mydocs.index') }}">Back</a>
    </div>
  </div>

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
            <div class="text-center text-muted py-5">
              <i class="bi bi-file-earmark-text fs-1 d-block mb-2"></i>
              Preview not available. Use Download instead.
            </div>
          @else
            <div class="text-center text-muted py-5">
              <i class="bi bi-exclamation-circle fs-1 d-block mb-2"></i>
              No file available for this document.
            </div>
          @endif
        </div>
      </div>
    </div>

    <!-- Right: Details -->
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <h6 class="fw-semibold mb-2">Details</h6>
          <dl class="row mb-0 small">
            <dt class="col-5">Status</dt>
            <dd class="col-7">
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
              </span>
            </dd>

            <dt class="col-5">Visibility</dt>
            <dd class="col-7">
              {{ $document->visibility ? str_replace('_',' ',ucfirst($document->visibility)) : '—' }}
            </dd>

            <dt class="col-5">Expires</dt>
            <dd class="col-7">
              @if($document->expires_at)
                <span class="{{ $document->isExpired() ? 'text-danger fw-semibold' : '' }}">
                  {{ $document->expires_at->format('M d, Y') }}
                </span>
              @else
                —
              @endif
            </dd>

            <dt class="col-5">Uploaded by</dt>
            <dd class="col-7">{{ $document->uploader?->name ?? '—' }}</dd>

            <dt class="col-5">Notes</dt>
            <dd class="col-7 text-break">{{ $document->notes ?: '—' }}</dd>

            <dt class="col-5">Uploaded on</dt>
            <dd class="col-7">
              {{ optional($document->created_at)->format('M d, Y h:i A') ?? '—' }}
            </dd>

            <dt class="col-5">Last Updated</dt>
            <dd class="col-7">
              {{ optional($document->updated_at)->format('M d, Y h:i A') ?? '—' }}
            </dd>
          </dl>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
