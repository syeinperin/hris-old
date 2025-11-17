@php
use Carbon\Carbon;
@endphp

<div class="table-scroll">
  <table class="table table-bordered align-middle text-center">
    <thead class="table-light">
      <tr>
        <th>Date</th>
        <th>Worked (hr)</th>
        <th>OT (hr)</th>
        <th>OT Pay</th>
        <th>ND (hr)</th>
        <th>ND Pay</th>
        <th>Holiday Pay</th>
        <th>Late Deduction</th>
        <th>Loan</th>
        <th>Monthly Contribution</th>
        <th>Gross</th>
        <th>Net</th>
        <th>Actions</th>
      </tr>
    </thead>

    <tbody>
      @forelse ($rows as $row)
        @php
          $isArray = is_array($row);
          $dateRaw = $isArray ? ($row['date'] ?? null) : ($row->date ?? null);
          $date = $dateRaw ? Carbon::parse($dateRaw)->format('Y-m-d') : '—';

          // Always allow editing by employee/date
          $actionRoute = route('payroll.edit', [
              'employee' => $employee->id,
              'date' => $date
          ]);
        @endphp

        <tr>
          <td>{{ $date }}</td>
          <td>{{ number_format($isArray ? ($row['worked_hr'] ?? $row['worked_hours'] ?? 0) : ($row->worked_hr ?? $row->worked_hours ?? 0), 2) }}</td>
          <td>{{ number_format($isArray ? ($row['ot_hr'] ?? $row['ot_hours'] ?? 0) : ($row->ot_hr ?? $row->ot_hours ?? 0), 2) }}</td>
          <td>₱{{ number_format($isArray ? ($row['ot_pay'] ?? 0) : ($row->ot_pay ?? 0), 2) }}</td>
          <td>{{ number_format($isArray ? ($row['nd_hr'] ?? $row['nd_hours'] ?? 0) : ($row->nd_hr ?? $row->nd_hours ?? 0), 2) }}</td>
          <td>₱{{ number_format($isArray ? ($row['nd_pay'] ?? 0) : ($row->nd_pay ?? 0), 2) }}</td>
<td>
  ₱{{ number_format(
      $isArray
        ? ($row['holiday_pay'] ?? 0)
        : ($row->holiday_pay ?? 0)
    , 2) }}
</td>

          <td>₱{{ number_format($isArray ? ($row['late'] ?? $row['late_deduction'] ?? 0) : ($row->late ?? $row->late_deduction ?? 0), 2) }}</td>
          <td>₱{{ number_format($isArray ? ($row['loan'] ?? $row['personal_loan'] ?? 0) : ($row->loan ?? $row->personal_loan ?? 0), 2) }}</td>
<td>
  ₱{{ number_format(
    (is_array($row)
      ? ($row['govt_deduction'] ?? $row['govt'] ?? 0)
      : ($row->govt_deduction ?? $row->govt ?? 0)
    ), 2) }}
</td>
          <td>₱{{ number_format($isArray ? ($row['gross'] ?? $row['gross_amount'] ?? 0) : ($row->gross ?? $row->gross_amount ?? 0), 2) }}</td>
          <td>₱{{ number_format($isArray ? ($row['net'] ?? $row['net_amount'] ?? 0) : ($row->net ?? $row->net_amount ?? 0), 2) }}</td>
          <td>
            <a href="{{ $actionRoute }}" class="btn btn-sm btn-outline-primary" title="Edit Payroll">
              <i class="bi bi-pencil-square"></i>
            </a>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="13" class="text-muted py-3">No records found for this cut-off.</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>
