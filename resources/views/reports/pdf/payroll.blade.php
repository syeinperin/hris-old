@php
    // Ensure rate_hr is always available
    $rate_hr = $rate_hr ?? ($rate ?? 0);

    // Prevent undefined loan variables
    $pagibigLoan = $pagibigLoan ?? 0;
    $sssLoan = $sssLoan ?? 0;
    $philhealthLoan = $philhealthLoan ?? 0;
    $cashAdvance = $cashAdvance ?? 0;
@endphp


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payslip</title>

<style>
  @font-face {
    font-family: 'NotoSans';
    src: url('{{ storage_path('fonts/NotoSans-Regular.ttf') }}') format('truetype');
  }

@page {
    size: 4.25in 8in;
    margin: 0.2in;
}


  body {
    font-family: 'NotoSans', DejaVu Sans, Helvetica, Arial, sans-serif;
    color: #222;
    font-size: 11px;
    margin: 0;
    padding: 0;
    line-height: 1.3;
  }

  .card {
    border: 1px solid #dedede;
    border-radius: 5px;
    padding: 14px 16px 8px;
  }

  .brand {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 6px;
  }

  .brand-left {
    display: flex;
    gap: 10px;
    align-items: flex-start;
  }

  .logo {
    width: 48px;
    height: 48px;
    object-fit: contain;
    margin-top: 2px;
  }

  table { width: 100%; border-collapse: collapse; margin-top: 2px; }
  th, td { border: 1px solid #e5e5e5; padding: 1px 1px; }
  thead th { background: #f6f6f6; font-weight: 700; }
  .col-desc { width: 48%; }
  .col-hrs  { width: 14%; text-align: right; }
  .col-rate { width: 18%; text-align: right; }
  .col-amt  { width: 22%; text-align: right; }
  .sec td  { background: #fafafa; font-weight: 700; }
  .sum td  { background: #fff; font-weight: 700; }
  .foot { text-align: center; margin-top: 6px; font-size: 9px; color: #777; }

</style>
</head>

<body>
<div class="card">

  {{-- HEADER --}}
  <div class="brand">
    <div class="brand-left">
      <img class="logo" src="{{ public_path('images/asiatex-logo.png') }}">

      <div>
        <div><strong>ASIATEX</strong></div>
        <div style="color:#666; font-size:10px;">Asia Textile Manufacturing Corporation</div>

        <div style="margin-top:4px; line-height:1.3;">
          <strong>Employee:</strong> {{ $employee->name }}<br>
          <strong>Code:</strong> {{ $employee->employee_code }}<br>
          <strong>Department:</strong> {{ optional($employee->department)->name ?? '—' }}<br>
          <strong>Position:</strong> {{ optional($employee->designation)->name ?? '—' }}<br>
          <strong>Rate/hr:</strong> ₱{{ number_format($rate_hr, 2) }}
        </div>
      </div>
    </div>

    <div style="text-align:right;">
      <h2 style="margin:0 0 3px;">PAYSLIP</h2>
      <div style="color:#666; font-size:10px;">
        {{ $period_start->format('M d, Y') }} – {{ $period_end->format('M d, Y') }}
      </div>
    </div>
  </div>

  {{-- TABLE --}}
  <table>
    <thead>
      <tr>
        <th class="col-desc">Description</th>
        <th class="col-hrs">Hours</th>
        <th class="col-rate">Rate</th>
        <th class="col-amt">Amount</th>
      </tr>
    </thead>

    <tbody>

      {{-- A. EARNINGS --}}
      <tr class="sec"><td colspan="4">[A] EARNINGS</td></tr>

      <tr>
        <td>Regular Hours</td>
        <td class="col-hrs">{{ number_format($worked_hours ?? 0, 2) }}</td>
        <td class="col-rate">₱{{ number_format($rate_hr, 2) }}</td>
        <td class="col-amt">₱{{ number_format($base_pay ?? 0, 2) }}</td>
      </tr>

      <tr>
        <td>Overtime</td>
        <td class="col-hrs">{{ number_format($ot_hours ?? 0, 2) }}</td>
        <td class="col-rate">₱{{ number_format($rate_hr * 1.25, 2) }}</td>
        <td class="col-amt">₱{{ number_format($ot_pay ?? 0, 2) }}</td>
      </tr>

      <tr>
        <td>Night Differential</td>
        <td class="col-hrs">{{ number_format($nd_hours ?? 0, 2) }}</td>
        <td class="col-rate">₱{{ number_format($rate_hr * 0.10, 2) }}</td>
        <td class="col-amt">₱{{ number_format($nd_pay ?? 0, 2) }}</td>
      </tr>

      <tr>
        <td>Regular Holiday Pay</td>
        <td class="col-hrs">0.00</td>
        <td class="col-rate">₱{{ number_format($rate_hr * 2.00, 2) }}</td>
        <td class="col-amt">₱{{ number_format($reg_hol_pay ?? 0, 2) }}</td>
      </tr>

      <tr>
        <td>Special Holiday Pay</td>
        <td class="col-hrs">0.00</td>
        <td class="col-rate">₱{{ number_format($rate_hr * 1.30, 2) }}</td>
        <td class="col-amt">₱{{ number_format($spec_hol_pay ?? 0, 2) }}</td>
      </tr>

      <tr>
        <td>Double Holiday Pay</td>
        <td class="col-hrs">0.00</td>
        <td class="col-rate">₱{{ number_format($rate_hr * 3.00, 2) }}</td>
        <td class="col-amt">₱{{ number_format($dbl_hol_pay ?? 0, 2) }}</td>
      </tr>

      <tr class="sum">
        <td>GROSS PAY</td><td></td><td></td>
        <td class="col-amt">₱{{ number_format($gross ?? 0, 2) }}</td>
      </tr>

      {{-- C. LOAN DEDUCTIONS --}}
      <tr class="sec"><td colspan="4">[C] LOAN DEDUCTIONS</td></tr>

      <tr><td>Pag-IBIG Loan</td><td></td><td></td><td class="col-amt">₱{{ number_format($pagibigLoan ?? 0, 2) }}</td></tr>
      <tr><td>SSS Loan</td><td></td><td></td><td class="col-amt">₱{{ number_format($sssLoan ?? 0, 2) }}</td></tr>
      <tr><td>PhilHealth Loan</td><td></td><td></td><td class="col-amt">₱{{ number_format($philhealthLoan ?? 0, 2) }}</td></tr>
      <tr><td>Cash Advance</td><td></td><td></td><td class="col-amt">₱{{ number_format($cashAdvance ?? 0, 2) }}</td></tr>

      <tr class="sum">
        <td>Total Loan Deductions</td><td></td><td></td>
        <td class="col-amt">₱{{ number_format(($pagibigLoan + $sssLoan + $philhealthLoan + $cashAdvance), 2) }}</td>
      </tr>

      {{-- E. SUMMARY --}}
      @php
        $total_ded = ($pagibigLoan + $sssLoan + $philhealthLoan + $cashAdvance);
      @endphp

      <tr class="sum">
        <td>TOTAL DEDUCTIONS</td><td></td><td></td>
        <td class="col-amt">₱{{ number_format($total_ded, 2) }}</td>
      </tr>

      <tr class="bold">
        <td>NET PAY</td><td></td><td></td>
        <td class="col-amt">₱{{ number_format(($gross ?? 0) - $total_ded, 2) }}</td>
      </tr>

    </tbody>
  </table>

  <div class="foot">
    Generated {{ now()->format('Y-m-d H:i') }} • Payslip Ref: {{ $employee->employee_code }}
  </div>

</div>
</body>
</html>
