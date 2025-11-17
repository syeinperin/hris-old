@extends('layouts.app')
@section('page_title','My Documents')

@push('styles')
<style>
  .doc-badges .badge{margin-right:6px}
  .card-hover{transition:.18s transform,.18s box-shadow}
  .card-hover:hover{transform:translateY(-2px);box-shadow:0 10px 22px rgba(0,0,0,.08)}
</style>
@endpush

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-0">My Documents</h3>
      <small class="text-muted">Upload and manage your Resume, Medical Docs, MDRs, and more.</small>
    </div>
    <a href="{{ route('mydocs.create') }}" class="btn btn-primary">
      <i class="bi bi-upload me-1"></i> Upload Document
    </a>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-6">
      <div class="card card-hover">
        <div class="card-body d-flex align-items-center justify-content-between">
          <div>
            <h6 class="mb-1">Certificate of Employment</h6>
            <small class="text-muted">Instantly generate a CoE PDF</small>
          </div>
          <a href="{{ route('mydocs.coe') }}" class="btn btn-outline-primary">
            <i class="bi bi-filetype-pdf me-1"></i> Download
          </a>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card card-hover">
        <div class="card-body d-flex align-items-center justify-content-between">
          <div>
            <h6 class="mb-1">Employee Information Sheet</h6>
            <small class="text-muted">Your latest EIS as PDF</small>
          </div>
          <a href="{{ route('mydocs.eis') }}" class="btn btn-outline-primary">
            <i class="bi bi-filetype-pdf me-1"></i> Download
          </a>
        </div>
      </div>
    </div>
  </div>

  @php
    $type = request('type');
    $pill = function($k,$label,$count) use($type){
      $active = $type===$k || (!$type && $k==='all');
      $url = $k==='all' ? route('mydocs.index') : route('mydocs.index',['type'=>$k]);
      return "<a href='{$url}' class='badge rounded-pill text-bg-".($active?'primary':'light')." me-2'>{$label} <span class=\"ms-1\">{$count}</span></a>";
    };
  @endphp

  <div class="doc-badges mb-3">
    {!! $pill('all','All',$docs->total()) !!}
    {!! $pill('resume','Resume',$counts['resume']) !!}
    {!! $pill('medical','Medical',$counts['medical']) !!}
    {!! $pill('mdr','MDRs',$counts['mdr']) !!}
    {!! $pill('other','Others',$counts['other']) !!}
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Title</th>
            <th>Type</th>
            <th>Version</th>
            <th>Status</th>
            <th>Expires</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($docs as $d)
            @if(!$type || $type==='all' || $type===$d->doc_type)
            <tr>
              <td>
                <a href="{{ route('mydocs.show',$d) }}" class="fw-semibold">{{ $d->title }}</a>
                <div class="small text-muted">Uploaded {{ $d->created_at->diffForHumans() }}</div>
              </td>
              <td class="text-capitalize">{{ $d->doc_type }}</td>
              <td>v{{ $d->version }}</td>
              <td>
                <span class="badge text-bg-{{ $d->status==='approved'?'success':($d->status==='rejected'?'danger':'secondary') }}">
                  {{ ucfirst($d->status) }}
                </span>
              </td>
              <td>
                @if($d->expires_at)
                  <span class="{{ $d->isExpired() ? 'text-danger' : 'text-muted' }}">
                    {{ $d->expires_at->format('M d, Y') }}
                  </span>
                @else
                  <span class="text-muted">—</span>
                @endif
              </td>
              <td class="text-end">
                <a href="{{ route('mydocs.download',$d) }}" class="btn btn-sm btn-outline-secondary">
                  <i class="bi bi-download"></i>
                </a>
                <a href="{{ route('mydocs.edit',$d) }}" class="btn btn-sm btn-outline-primary">
                  <i class="bi bi-pencil-square"></i>
                </a>
                <form action="{{ route('mydocs.destroy',$d) }}" method="POST" class="d-inline"
                      onsubmit="return confirm('Delete this document?')">
                  @csrf @method('DELETE')
                  <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
              </td>
            </tr>
            @endif
          @empty
            <tr><td colspan="6" class="text-center text-muted py-4">No documents yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="card-body">
      {{ $docs->withQueryString()->links() }}
    </div>
  </div>
</div>
@endsection
