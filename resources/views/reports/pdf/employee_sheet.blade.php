<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Employee Information – {{ $employee->employee_code }}</title>
  <style>
    @page { margin: 40px 35px; }

    /* Font family */
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');
    body {
      font-family: 'Poppins', Arial, Helvetica;
      font-size: 11.8px;
      color: #333;
      line-height: 1.5;
    }

    /* Header */
    .header {
      text-align: center;
      border-bottom: 3px solid #26264e;
      padding-bottom: 10px;
      margin-bottom: 18px;
    }
    .header img {
      max-height: 60px;
      margin-bottom: 5px;
    }
    .title {
      font-size: 20px;
      font-weight: 700;
      color: #26264e;
      margin: 0;
    }
    .date {
      font-size: 11px;
      color: #777;
    }

    /* Section titles */
    .section-title {
      background: #26264e;
      color: #fff;
      padding: 6px 10px;
      font-size: 13.5px;
      font-weight: 600;
      border-radius: 6px;
      margin: 25px 0 6px 0;
    }

    /* Tables */
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 5px;
    }
    td {
      padding: 6px 8px;
      vertical-align: top;
    }
    .label {
      width: 25%;
      font-weight: 600;
      color: #26264e;
    }

    /* Alternating background for better readability */
    tr:nth-child(even) td {
      background: #f8f9fb;
    }

    /* Profile box */
    .profile-box {
      border: 1.5px solid #ccc;
      border-radius: 6px;
      width: 95px;
      height: 95px;
      object-fit: cover;
      padding: 2px;
    }

    /* Footer */
    footer {
      position: fixed;
      bottom: 15px;
      left: 0;
      right: 0;
      text-align: center;
      font-size: 10px;
      color: #666;
    }

    /* Subtle section divider */
    hr {
      border: 0;
      border-top: 1px solid #ccc;
      margin: 10px 0;
    }
  </style>
</head>
<body>

  {{-- HEADER --}}
  <div class="header">
    @if(config('app.logo') && file_exists(public_path('storage/' . config('app.logo'))))
      <img src="{{ public_path('storage/' . config('app.logo')) }}" alt="Company Logo">
    @endif
    <p class="title">Employee Information Sheet</p>
    <p class="date">Generated on {{ now()->format('F j, Y') }}</p>
  </div>

  {{-- EMPLOYEE DETAILS --}}
  <div class="section">
    <div class="section-title">Employee Details</div>
    <table>
      <tr>
        <td class="label">Full Name</td>
        <td>{{ $employee->name ?? '–' }}</td>
        <td rowspan="5" colspan="2" style="text-align:right;">
          @if($employee->profile_picture && file_exists(public_path('storage/' . $employee->profile_picture)))
            <img src="{{ public_path('storage/' . $employee->profile_picture) }}" class="profile-box" alt="Profile Photo">
          @else
            <div class="profile-box" style="display:flex;align-items:center;justify-content:center;color:#888;font-size:11px;">No Photo</div>
          @endif
        </td>
      </tr>
      <tr>
        <td class="label">Employee Code</td>
        <td>{{ $employee->employee_code ?? '–' }}</td>
      </tr>
      <tr>
        <td class="label">Gender</td>
        <td>{{ ucfirst($employee->gender ?? '–') }}</td>
      </tr>
      <tr>
        <td class="label">Date of Birth</td>
        <td>
          @if($employee->dob)
            {{ \Carbon\Carbon::parse($employee->dob)->format('F j, Y') }}
          @else
            –
          @endif
        </td>
      </tr>
      <tr>
        <td class="label">Age</td>
        <td>
          @if($employee->dob)
            {{ \Carbon\Carbon::parse($employee->dob)->age }} years
          @else
            –
          @endif
        </td>
      </tr>
      <tr>
        <td class="label">Employment Type</td>
        <td>{{ ucfirst($employee->employment_type ?? '–') }}</td>
        <td class="label">Employment Status</td>
        <td>{{ ucfirst($employee->employment_type ?? '–') }}</td>
      </tr>
      <tr>
        <td class="label">Role</td>
        <td>{{ data_get($employee, 'user.roles.0.name', '–') }}</td>
        <td class="label">Account Status</td>
        <td>{{ ucfirst(data_get($employee, 'user.status', '–')) }}</td>
      </tr>
      <tr>
        <td class="label">Last Login</td>
        <td colspan="3">
          @php $lastLogin = data_get($employee, 'user.last_login_at'); @endphp
          {{ $lastLogin ? \Carbon\Carbon::parse($lastLogin)->format('F j, Y g:i A') : '–' }}
        </td>
      </tr>
    </table>
  </div>

  {{-- ADDRESS --}}
  <div class="section">
    <div class="section-title">Address Information</div>
    <table>
      <tr>
        <td class="label">Current Address</td>
        <td colspan="3">{{ $employee->current_address ?? '–' }}</td>
      </tr>
      <tr>
        <td class="label">Permanent Address</td>
        <td colspan="3">{{ $employee->permanent_address ?? '–' }}</td>
      </tr>
    </table>
  </div>

  {{-- FAMILY --}}
  <div class="section">
    <div class="section-title">Family & Previous Employment</div>
    <table>
      <tr>
        <td class="label">Father's Name</td><td>{{ $employee->father_name ?? '–' }}</td>
        <td class="label">Mother's Name</td><td>{{ $employee->mother_name ?? '–' }}</td>
      </tr>
      <tr>
        <td class="label">Previous Company</td><td>{{ $employee->previous_company ?? '–' }}</td>
        <td class="label">Previous Job Title</td><td>{{ $employee->previous_job_title ?? '–' }}</td>
      </tr>
      <tr>
        <td class="label">Experience (Years)</td><td>{{ $employee->experience_years ?? '–' }}</td>
        <td class="label">Nationality</td><td>{{ $employee->nationality ?? '–' }}</td>
      </tr>
    </table>
  </div>

  {{-- GOVERNMENT IDS --}}
  <div class="section">
    <div class="section-title">Government / Benefit IDs</div>
    <table>
      <tr>
        <td class="label">GSIS No.</td><td>{{ $employee->gsis_id_no ?? '–' }}</td>
        <td class="label">Pag-IBIG No.</td><td>{{ $employee->pagibig_id_no ?? '–' }}</td>
      </tr>
      <tr>
        <td class="label">PhilHealth No.</td><td>{{ $employee->philhealth_no ?? '–' }}</td>
        <td class="label">TIN No.</td><td>{{ $employee->tin_no ?? '–' }}</td>
      </tr>
      <tr>
        <td class="label">SSS No.</td><td>{{ $employee->sss_no ?? '–' }}</td>
        <td class="label">Agency Emp. No.</td><td>{{ $employee->agency_employee_no ?? '–' }}</td>
      </tr>
    </table>
  </div>

  {{-- JOB INFO --}}
  <div class="section">
    <div class="section-title">Job Information</div>
    <table>
      <tr>
        <td class="label">Department</td><td>{{ optional($employee->department)->name ?? '–' }}</td>
        <td class="label">Designation</td><td>{{ optional($employee->designation)->name ?? '–' }}</td>
      </tr>
      <tr>
        <td class="label">Start Date</td><td>{{ $employee->start_date ? \Carbon\Carbon::parse($employee->start_date)->format('F j, Y') : '–' }}</td>
        <td class="label">Salary</td><td>{{ $employee->salary ? '₱' . number_format($employee->salary, 2) : '–' }}</td>
      </tr>
      <tr>
        <td class="label">Supervisor</td><td>{{ optional($employee->supervisor)->name ?? '–' }}</td>
        <td class="label">Work Location</td><td>{{ $employee->work_location ?? '–' }}</td>
      </tr>
    </table>
  </div>

  {{-- EMERGENCY CONTACT --}}
  <div class="section">
    <div class="section-title">Emergency Contact</div>
    <table>
      <tr>
        <td class="label">Name</td><td>{{ $employee->emergency_contact_name ?? '–' }}</td>
        <td class="label">Relationship</td><td>{{ $employee->emergency_contact_relation ?? '–' }}</td>
      </tr>
      <tr>
        <td class="label">Phone</td><td>{{ $employee->emergency_contact_phone ?? '–' }}</td>
        <td class="label">Address</td><td>{{ $employee->emergency_contact_address ?? '–' }}</td>
      </tr>
    </table>
  </div>

  <footer>© {{ date('Y') }} Asia Textile Mills, Inc. — Generated via HRIS</footer>
</body>
</html>
