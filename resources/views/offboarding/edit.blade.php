@extends('layouts.app')

@section('page_title','Edit Offboarding')

@section('content')
<div class="container-fluid">
  <div class="card shadow-sm">
    <div class="card-header bg-white">
      <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Offboarding #{{ $offboarding->id }}</h5>
    </div>
    <form action="{{ route('offboarding.update', $offboarding) }}" method="POST">
      @csrf @method('PUT')
      <div class="card-body">
        @include('offboarding._form', ['offboarding' => $offboarding, 'mode' => 'edit'])
      </div>
      <div class="card-footer bg-white d-flex justify-content-between">
        <a href="{{ route('offboarding.show', $offboarding) }}" class="btn btn-outline-secondary">Back</a>
        <div class="d-flex gap-2">
          @if(!in_array($offboarding->status, ['completed','cancelled']))
            <button class="btn btn-primary"><i class="bi bi-save2 me-1"></i> Update</button>
          @endif
        </div>
      </div>
    </form>
  </div>
</div>
@endsection
