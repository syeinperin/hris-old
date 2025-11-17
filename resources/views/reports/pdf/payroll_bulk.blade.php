<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Payslips (Bulk)</title>

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

  /* HEADER */
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
  .title { line-height: 1.15; }
  .company { font-weight: 700; letter-spacing: .2px; }
  .muted { color: #666; font-size: 10px; }
  .meta { text-align: right; }
  .meta h2 {
    margin: 0 0 3px;
    font-size: 14px;
    letter-spacing: .6px;
  }
  .kv { margin-top: 4px; line-height: 1.3; }

  /* TABLE FORMATTING */
  table { width: 100%; border-collapse: collapse; margin-top: 4px; }
  th, td { border: 1px solid #e5e5e5; padding: 1px 1px; }
  thead th {
    background: #f6f6f6;
    font-weight: 700;
  }
  .col-desc { width: 48%; }
  .col-hrs  { width: 14%; text-align: right; }
  .col-rate { width: 18%; text-align: right; }
  .col-amt  { width: 20%; text-align: right; }
  .sec td { background: #fafafa; font-weight: 700; }
  .sum td { background: #fff; font-weight: 700; }
  .net td { background: #eef6ff; font-weight: 800; }
  .right { text-align: right; }
  .foot { margin-top: 6px; text-align: center; color: #777; font-size: 9px; }
</style>

</head>
<body>

@foreach($items as $x)
@php
  $employee     = $x['employee'];
  $period_start = $x['period_start'];
  $period_end   = $x['period_end'];

  $rate_hr      = $x['rate'] ?? 0;
  $worked_hours = $x['worked_hours'] ?? 0;
  $ot_hours     = $x['ot_hours'] ?? 0;
  $nd_hours     = $x['nd_hours'] ?? 0;

  $base_pay     = $x['base_pay'] ?? 0;
  $ot_pay       = $x['ot_pay'] ?? 0;
  $nd_pay       = $x['nd_pay'] ?? 0;
  $reg_hol_pay  = $x['reg_hol_pay'] ?? 0;
  $spec_hol_pay = $x['spec_hol_pay'] ?? 0;
  $dbl_hol_pay  = $x['dbl_hol_pay'] ?? 0;

  $gross        = $x['gross'] ?? 0;

  // LOANS
  $pagibigLoan     = $x['pagibigLoan'] ?? 0;
  $sssLoan         = $x['sssLoan'] ?? 0;
  $philhealthLoan  = $x['philhealthLoan'] ?? 0;
  $cashAdvance     = $x['cashAdvance'] ?? 0;

  // GOV PREMIUMS
  $sss   = $x['sss'] ?? 0;
  $phil  = $x['phil'] ?? 0;
  $pag   = $x['pag'] ?? 0;

  // TOTALS
  $loan_total = ($pagibigLoan + $sssLoan + $philhealthLoan + $cashAdvance);
  $gov_total  = ($sss + $phil + $pag);
  $total_ded  = $loan_total + $gov_total;
  $net_safe   = max(0, round($gross - $total_ded, 2));
@endphp

{{-- CARD --}}
<div class="card">

  {{-- HEADER --}}
  <div class="brand">
    <div class="brand-left">
      <img class="logo" src="{{ public_path('images/asiatex-logo.png') }}" alt="ASIATEX">
      <div class="title">
        <div class="company">ASIATEX</div>
        <div class="muted">Asia Textile Manufacturing Corporation</div>

        <div class="kv">
          <strong>Employee:</strong> {{ $employee->name }}<br>
          <strong>Code:</strong> {{ $employee->employee_code }}<br>
          <strong>Department:</strong> {{ optional($employee->department)->name ?? '—' }}<br>
          <strong>Position:</strong> {{ optional($employee->designation)->name ?? '—' }}<br>
          <strong>Rate/hr:</strong> ₱{{ number_format($rate_hr, 2) }}
        </div>
      </div>
    </div>

    <div class="meta">
      <h2>PAYSLIP</h2>
      <div class="muted">
        Period:
        {{ $period_start->format('M d, Y') }}
        –
        {{ $period_end->format('M d, Y') }}
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
        <td class="col-hrs">{{ number_format($worked_hours, 2) }}</td>
        <td class="col-rate">₱{{ number_format($rate_hr, 2) }}</td>
        <td class="col-amt">₱{{ number_format($base_pay, 2) }}</td>
      </tr>

      <tr>
        <td>Overtime</td>
        <td class="col-hrs">{{ number_format($ot_hours, 2) }}</td>
        <td class="col-rate">₱{{ number_format($rate_hr * 1.25, 2) }}</td>
        <td class="col-amt">₱{{ number_format($ot_pay, 2) }}</td>
      </tr>

      <tr>
        <td>Night Differential</td>
        <td class="col-hrs">{{ number_format($nd_hours, 2) }}</td>
        <td class="col-rate">₱{{ number_format($rate_hr * 0.10, 2) }}</td>
        <td class="col-amt">₱{{ number_format($nd_pay, 2) }}</td>
      </tr>

      <tr>
        <td>Regular Holiday Pay</td>
        <td class="col-hrs">0.00</td>
        <td class="col-rate">₱{{ number_format($rate_hr * 2.00, 2) }}</td>
        <td class="col-amt">₱{{ number_format($reg_hol_pay, 2) }}</td>
      </tr>

      <tr>
        <td>Special Holiday Pay</td>
        <td class="col-hrs">0.00</td>
        <td class="col-rate">₱{{ number_format($rate_hr * 1.30, 2) }}</td>
        <td class="col-amt">₱{{ number_format($spec_hol_pay, 2) }}</td>
      </tr>

      <tr>
        <td>Double Holiday Pay</td>
        <td class="col-hrs">0.00</td>
        <td class="col-rate">₱{{ number_format($rate_hr * 3.00, 2) }}</td>
        <td class="col-amt">₱{{ number_format($dbl_hol_pay, 2) }}</td>
      </tr>

      <tr class="sum">
        <td>GROSS PAY</td><td></td><td></td>
        <td class="col-amt">₱{{ number_format($gross, 2) }}</td>
      </tr>

      {{-- C. LOANS --}}
      <tr class="sec"><td colspan="4">[C] LOAN DEDUCTIONS</td></tr>
      <tr><td>Pag-IBIG Loan</td><td></td><td></td><td class="col-amt">₱{{ number_format($pagibigLoan, 2) }}</td></tr>
      <tr><td>SSS Loan</td><td></td><td></td><td class="col-amt">{{ number_format($sssLoan, 2) }}</td></tr>
      <tr><td>PhilHealth Loan</td><td></td><td></td><td class="col-amt">{{ number_format($philhealthLoan, 2) }}</td></tr>
      <tr><td>Cash Advance</td><td></td><td></td><td class="col-amt">{{ number_format($cashAdvance, 2) }}</td></tr>

      <tr class="sum">
        <td>Total Loan Deductions</td><td></td><td></td>
        <td class="col-amt">₱{{ number_format($loan_total, 2) }}</td>
      </tr>


      <tr class="sum">
        <td>Total Government Deductions</td><td></td><td></td>
        <td class="col-amt">₱{{ number_format($gov_total, 2) }}</td>
      </tr>

      {{-- NET --}}
      <tr class="sum">
        <td>TOTAL DEDUCTIONS</td><td></td><td></td>
        <td class="col-amt">₱{{ number_format($total_ded, 2) }}</td>
      </tr>

      <tr class="bold">
        <td>NET PAY</td><td></td><td></td>
        <td class="col-amt">₱{{ number_format($net_safe, 2) }}</td>
      </tr>

    </tbody>
  </table>

  <div class="foot">
    Generated {{ now()->format('Y-m-d H:i') }} • {{ $employee->employee_code }}
  </div>

</div>
@endforeach

</body>
</html>
