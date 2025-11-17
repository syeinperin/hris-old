@extends('layouts.app')

@section('page_title', 'My Profile')

@section('content')
<div class="container mt-4">
  <div class="card shadow-sm">
    <div class="card-header bg-white">
      <h4 class="mb-0"><i class="bi bi-person-circle me-2"></i>My Profile</h4>
    </div>

    <div class="card-body">
      @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @endif

      <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

 {{-- PROFILE PICTURE --}}
<div class="mb-4 text-center">
  <img
    src="{{ $employee->profile_picture_url }}"
    class="rounded-circle mb-2 border"
    width="130" height="130"
    style="object-fit:cover; background:#f8f9fa;"
    alt="Profile Picture"
  >
  <br>
  <label class="form-label mt-2 fw-semibold">Change Picture</label>
  <input
    type="file"
    name="profile_picture"
    class="form-control @error('profile_picture') is-invalid @enderror"
  >
  @error('profile_picture')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>


        {{-- ACCOUNT & PERSONAL --}}
        <div class="card mb-4">
          <div class="card-header fw-semibold">Account & Personal</div>
          <div class="card-body row g-3">

            <div class="col-md-6 form-floating">
              <input type="email" name="email" class="form-control" value="{{ old('email',$user->email) }}" required>
              <label>Email</label>
            </div>

            <div class="col-md-6 form-floating">
              <input type="password" name="password" class="form-control" placeholder="New Password (optional)">
              <label>New Password</label>
            </div>

            <div class="col-md-6 form-floating">
              <input type="password" name="password_confirmation" class="form-control" placeholder="Confirm Password">
              <label>Confirm Password</label>
            </div>

            <div class="col-md-6 form-floating">
              <input type="text" name="first_name" class="form-control" value="{{ old('first_name',$employee->first_name) }}" required>
              <label>First Name</label>
            </div>

            <div class="col-md-6 form-floating">
              <input type="text" name="middle_name" class="form-control" value="{{ old('middle_name',$employee->middle_name) }}">
              <label>Middle Name</label>
            </div>

            <div class="col-md-6 form-floating">
              <input type="text" name="last_name" class="form-control" value="{{ old('last_name',$employee->last_name) }}" required>
              <label>Last Name</label>
            </div>

            <div class="col-md-4 form-floating">
              <select name="gender" class="form-select" required>
                <option value="male"   {{ old('gender',$employee->gender)=='male'?'selected':'' }}>Male</option>
                <option value="female" {{ old('gender',$employee->gender)=='female'?'selected':'' }}>Female</option>
                <option value="other"  {{ old('gender',$employee->gender)=='other'?'selected':'' }}>Other</option>
              </select>
              <label>Gender</label>
            </div>

                   {{-- 🟩 NEW CIVIL STATUS FIELD --}}
            <div class="col-md-4 form-floating">
              <select name="civil_status" class="form-select" required>
                <option value="">Select status...</option>
                <option value="single"    {{ old('civil_status',$employee->civil_status)=='single'?'selected':'' }}>Single</option>
                <option value="married"   {{ old('civil_status',$employee->civil_status)=='married'?'selected':'' }}>Married</option>
                <option value="widowed"   {{ old('civil_status',$employee->civil_status)=='widowed'?'selected':'' }}>Widowed</option>
                <option value="separated" {{ old('civil_status',$employee->civil_status)=='separated'?'selected':'' }}>Separated</option>
              </select>
              <label>Civil Status</label>
            </div>


            <div class="col-md-4 form-floating">
              <input type="date" name="dob" class="form-control"
                     value="{{ old('dob',$employee->dob?->format('Y-m-d')) }}" required>
              <label>Date of Birth</label>
            </div>

            <div class="col-md-12 form-floating">
              <input type="text" name="permanent_address" class="form-control"
                     value="{{ old('permanent_address',$employee->permanent_address) }}">
              <label>Permanent Address</label>
            </div>
          </div>
        </div>

        {{-- WORK & BENEFITS --}}
        <div class="card mb-4">
          <div class="card-header fw-semibold">Work & Benefits</div>
          <div class="card-body row g-3">

            <div class="col-md-4 form-floating">
              <select name="department_id" class="form-select" @unless($isHr) disabled @endunless>
                <option value="">Department…</option>
                @foreach($departments as $id => $label)
                  <option value="{{ $id }}" {{ old('department_id',$employee->department_id)==$id?'selected':'' }}>
                    {{ $label }}
                  </option>
                @endforeach
              </select>
              <label>Department</label>
            </div>

            <div class="col-md-4 form-floating">
              <select name="designation_id" class="form-select" @unless($isHr) disabled @endunless>
                <option value="">Designation…</option>
                @foreach($designations as $id => $label)
                  <option value="{{ $id }}" {{ old('designation_id',$employee->designation_id)==$id?'selected':'' }}>
                    {{ $label }}
                  </option>
                @endforeach
              </select>
              <label>Designation</label>
            </div>

            <div class="col-md-4 form-floating">
              <select name="schedule_id" class="form-select" @unless($isHr) disabled @endunless>
                <option value="">Schedule…</option>
                @foreach($schedules as $id => $label)
                  <option value="{{ $id }}" {{ old('schedule_id',$employee->schedule_id)==$id?'selected':'' }}>
                    {{ $label }}
                  </option>
                @endforeach
              </select>
              <label>Schedule</label>
            </div>

            <div class="col-md-4 form-floating">
              <select name="employment_type" class="form-select" @unless($isHr) disabled @endunless>
                @foreach($employmentTypes as $key => $label)
                  <option value="{{ $key }}" {{ old('employment_type',$employee->employment_type)==$key?'selected':'' }}>
                    {{ $label }}
                  </option>
                @endforeach
              </select>
              <label>Employment Type</label>
            </div>

            <div class="col-md-4 form-floating">
              <input type="date" name="employment_start_date" class="form-control"
                     value="{{ old('employment_start_date',$employee->employment_start_date?->format('Y-m-d')) }}"
                     @unless($isHr) disabled @endunless>
              <label>Start Date</label>
            </div>

            <div class="col-md-4 form-floating">
              <input type="date" name="employment_end_date" class="form-control"
                     value="{{ old('employment_end_date',$employee->employment_end_date?->format('Y-m-d')) }}"
                     @unless($isHr) disabled @endunless>
              <label>End Date</label>
            </div>

            <div class="col-md-4 form-floating">
              <input type="text" name="sss_no" class="form-control"
                     value="{{ old('sss_no',$employee->sss_no) }}" @unless($isHr) readonly @endunless>
              <label>SSS No.</label>
            </div>

            <div class="col-md-4 form-floating">
              <input type="text" name="pagibig_id_no" class="form-control"
                     value="{{ old('pagibig_id_no',$employee->pagibig_id_no) }}" @unless($isHr) readonly @endunless>
              <label>PAG-IBIG ID No.</label>
            </div>

            <div class="col-md-4 form-floating">
              <input type="text" name="philhealth_tin_id_no" class="form-control"
                     value="{{ old('philhealth_tin_id_no',$employee->philhealth_tin_id_no) }}" @unless($isHr) readonly @endunless>
              <label>PhilHealth TIN No.</label>
            </div>
          </div>
        </div>

        {{-- EDUCATION --}}
        <div class="card mb-4">
          <div class="card-header fw-semibold">Education</div>
          <div class="card-body row g-3">
            <div class="col-md-6 form-floating">
              <input type="text" name="elementary_school" class="form-control" value="{{ old('elementary_school',$employee->elementary_school) }}">
              <label>Elementary School</label>
            </div>
            <div class="col-md-6 form-floating">
              <input type="number" name="elementary_year_graduated" class="form-control" value="{{ old('elementary_year_graduated',$employee->elementary_year_graduated) }}">
              <label>Year Graduated</label>
            </div>

            <div class="col-md-6 form-floating">
              <input type="text" name="high_school" class="form-control" value="{{ old('high_school',$employee->high_school) }}">
              <label>High School</label>
            </div>
            <div class="col-md-6 form-floating">
              <input type="number" name="high_school_year_graduated" class="form-control" value="{{ old('high_school_year_graduated',$employee->high_school_year_graduated) }}">
              <label>Year Graduated</label>
            </div>

            <div class="col-md-6 form-floating">
              <input type="text" name="college" class="form-control" value="{{ old('college',$employee->college) }}">
              <label>College</label>
            </div>
            <div class="col-md-6 form-floating">
              <input type="number" name="college_year_graduated" class="form-control" value="{{ old('college_year_graduated',$employee->college_year_graduated) }}">
              <label>Year Graduated</label>
            </div>

            <div class="col-md-6 form-floating">
              <input type="text" name="degree_received" class="form-control" value="{{ old('degree_received',$employee->degree_received) }}">
              <label>Degree Received</label>
            </div>

            <div class="col-md-6 form-floating">
              <textarea name="special_skills" class="form-control" style="height: 100px">{{ old('special_skills',$employee->special_skills) }}</textarea>
              <label>Special Skills</label>
            </div>
          </div>
        </div>

        {{-- WORK HISTORY --}}
        <div class="card mb-4">
          <div class="card-header fw-semibold">Work History</div>
          <div class="card-body row g-3">
            <div class="col-md-4 form-floating">
              <input type="text" name="emp1_company" class="form-control" value="{{ old('emp1_company',$employee->emp1_company) }}">
              <label>Company</label>
            </div>
            <div class="col-md-4 form-floating">
              <input type="text" name="emp1_position" class="form-control" value="{{ old('emp1_position',$employee->emp1_position) }}">
              <label>Position</label>
            </div>
            <div class="col-md-2 form-floating">
              <input type="date" name="emp1_from" class="form-control" value="{{ old('emp1_from',$employee->emp1_from) }}">
              <label>From</label>
            </div>
            <div class="col-md-2 form-floating">
              <input type="date" name="emp1_to" class="form-control" value="{{ old('emp1_to',$employee->emp1_to) }}">
              <label>To</label>
            </div>
          </div>
        </div>

        {{-- CHARACTER REFERENCES --}}
        <div class="card mb-4">
          <div class="card-header fw-semibold">Character References</div>
          <div class="card-body row g-3">
            <div class="col-md-3 form-floating">
              <input type="text" name="char1_name" class="form-control" value="{{ old('char1_name',$employee->char1_name) }}">
              <label>Name</label>
            </div>
            <div class="col-md-3 form-floating">
              <input type="text" name="char1_position" class="form-control" value="{{ old('char1_position',$employee->char1_position) }}">
              <label>Position</label>
            </div>
            <div class="col-md-3 form-floating">
              <input type="text" name="char1_company" class="form-control" value="{{ old('char1_company',$employee->char1_company) }}">
              <label>Company</label>
            </div>
            <div class="col-md-3 form-floating">
              <input type="text" name="char1_contact" class="form-control" value="{{ old('char1_contact',$employee->char1_contact) }}">
              <label>Contact</label>
            </div>
          </div>
        </div>

        <div class="text-end">
          <button class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Optional live preview --}}
<script>
document.querySelector('input[name="profile_picture"]').addEventListener('change', function(e) {
  const [file] = e.target.files;
  if (file) {
    const preview = document.querySelector('img[alt="Avatar"]');
    preview.src = URL.createObjectURL(file);
  }
});
</script>
@endsection
