@extends('layouts.app')
@section('page_title','Edit Document')

@section('content')
<div class="container" style="max-width:760px;">
  <h3 class="mb-3">Edit Document</h3>
  <div class="card">
    <div class="card-body">
      <form action="{{ route('mydocs.update',$document) }}" method="POST" enctype="multipart/form-data">
        @csrf @method('PUT')

        <div class="mb-3">
          <label class="form-label">Title</label>
          <input type="text" name="title" class="form-control" required value="{{ old('title',$document->title) }}">
        </div>

        <div class="mb-3">
          <label class="form-label">Type</label>
          <select name="doc_type" class="form-select" required>
            @foreach(['resume','medical','mdr','other'] as $t)
              <option value="{{ $t }}" @selected($document->doc_type===$t)>{{ ucfirst($t) }}</option>
            @endforeach
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Replace File (optional)</label>
          <input type="file" name="file" class="form-control">
          <small class="text-muted">Leave blank to keep current file.</small>
        </div>

        <div class="mb-3">
          <label class="form-label">Notes (optional)</label>
          <textarea name="notes" class="form-control" rows="3">{{ old('notes',$document->notes) }}</textarea>
        </div>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Visibility</label>
            <select name="visibility" class="form-select">
              @foreach(['employee'=>'Employee only','private_employee'=>'Private (me only)','hr'=>'HR','supervisor'=>'Supervisor','hr_supervisor'=>'HR & Supervisor'] as $k=>$v)
                <option value="{{ $k }}" @selected($document->visibility===$k)>{{ $v }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Expires At (optional)</label>
            <input type="date" name="expires_at" class="form-control" value="{{ optional($document->expires_at)->toDateString() }}">
          </div>
        </div>

        <div class="mt-4 d-flex gap-2">
          <a href="{{ route('mydocs.show',$document) }}" class="btn btn-light">Cancel</a>
          <button class="btn btn-primary">
            <i class="bi bi-save me-1"></i> Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
