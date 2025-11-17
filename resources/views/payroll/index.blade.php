@extends('layouts.app')

@section('page_title','Payroll Summary')

@section('content')
<div class="container-fluid">
  <div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
      <h4 class="mb-0">
        <i class="bi bi-cash-stack me-2"></i> Payroll Summary
      </h4>
      <div class="d-flex gap-2">
        <a href="{{ route('designations.index') }}" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-percent me-1"></i> Salary Rates
        </a>
        <a href="{{ route('loans.index') }}" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-journal-medical me-1"></i> Loans
        </a>
      </div>
    </div>

    <div class="card-body">
      {{-- Uniform filter: keyword + start/end date --}}
      <x-search-bar
        :action="route('payroll.index')"
        placeholder="Search name or code…"
        :filters="[]"
        :showDateRange="true"
        startName="start_date"
        endName="end_date"
      />

      {{-- Summary table --}}
      <div class="table-scroll mb-3">
        <table class="table table-hover align-middle table-sticky">
          <thead class="table-light">
            <tr>
              <th>Code</th>
              <th>Name</th>
              <th class="text-end">Net Pay</th>
              <th class="text-center" style="width:1%;">Action</th>
            </tr>
          </thead>
          <tbody>
@forelse($paginator as $row)
              <tr>
                <td class="text-nowrap">{{ $row['employee_code'] }}</td>
                <td>{{ $row['employee_name'] }}</td>
                <td class="text-end text-nowrap">₱{{ number_format((float)$row['net_pay'], 2) }}</td>
                <td class="text-center">
                  @php
                    $monthParam = substr(request('start_date', $date), 0, 7);
                  @endphp
                  <a
  href="{{ route('payroll.show', ['employee' => $row['employee_id']]) }}?month={{ $monthParam }}"                    class="btn btn-sm btn-primary"
                  >
                    View
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="text-center text-muted py-4">
                  No payroll data{{ request('start_date') && request('end_date') ? ' for the selected range.' : " for $date." }}
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{-- Pagination --}}
      <div class="d-flex justify-content-between align-items-center mt-4">
<small class="text-muted">
  Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}
  of {{ $paginator->total() }}
</small>
{{ $paginator->withQueryString()->links('pagination::bootstrap-5') }}

      </div>
    </div>
  </div>
</div>
@endsection
