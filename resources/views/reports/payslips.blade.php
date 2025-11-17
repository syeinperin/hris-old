@extends('layouts.app')

@section('page_title','Payslip Reports')

@section('content')
<div class="container-fluid py-4">
  <div class="card shadow-sm mb-4">

    <div class="card-header bg-white d-flex align-items-center justify-content-between">
      <h4 class="mb-0"><i class="bi bi-receipt me-2"></i> Payslip Reports</h4>

      <div class="d-flex gap-2">
        <form class="d-flex gap-2" method="GET" action="{{ route('reports.payslips.list') }}">
          <input type="date" name="from" class="form-control form-control-sm" value="{{ $from }}">
          <span class="align-self-center">to</span>
          <input type="date" name="to" class="form-control form-control-sm" value="{{ $to }}">
          <button class="btn btn-primary btn-sm"><i class="bi bi-search"></i> Filter</button>
        </form>

        {{-- Bulk PDF button --}}
        <a class="btn btn-dark btn-sm"
           href="{{ route('reports.pdf.payroll_bulk', ['from'=>$from, 'to'=>$to]) }}">
          <i class="bi bi-file-earmark-pdf me-1"></i> Bulk PDF
        </a>
      </div>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:64px;">#</th>
              <th>Employee</th>
              <th>Code</th>
              <th>Days Worked</th>
              <th class="text-end" style="width:200px;">Action</th>
            </tr>
          </thead>
          <tbody>
          @forelse($employees as $i => $e)
            <tr>
              <td>{{ $employees->firstItem() + $i }}</td>
              <td>{{ $e->name }}</td>
              <td>{{ $e->employee_code }}</td>
              <td>{{ $e->days_worked }}</td>
              <td class="text-end">
             <a class="btn btn-sm btn-outline-primary"
   href="{{ route('reports.payslips.download', [
       'employee' => $e->id,
       'from'     => $from,
       'to'       => $to,
   ]) }}">
   <i class="bi bi-download me-1"></i> Download
</a>

              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="text-center text-muted py-4">No employees found.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
      <div class="p-3">
        {{ $employees->links() }}
      </div>
    </div>
  </div>
</div>
@endsection
