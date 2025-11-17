{{-- resources/views/employees/show.blade.php --}}
<div class="modal fade" id="viewEmployeeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-white border-bottom">
        <h5 class="modal-title d-flex align-items-center gap-2">
          <i class="bi bi-person-badge-fill"></i>
          <span class="ve-employee_code">—</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body" style="max-height: calc(100vh - 150px); overflow-y:auto;">
        <div class="row g-3">
          {{-- LEFT COLUMN --}}
          <div class="col-lg-8">
            {{-- Personal --}}
            <table class="table table-bordered mb-4">
              <thead class="table-light">
                <tr><th colspan="2" class="text-center">Personal</th></tr>
              </thead>
              <tbody>
                <tr><th>First Name</th><td class="ve-first_name">—</td></tr>
                <tr><th>Middle Name</th><td class="ve-middle_name">—</td></tr>
                <tr><th>Last Name</th><td class="ve-last_name">—</td></tr>
                <tr><th>Gender</th><td class="ve-gender">—</td></tr>
                <tr><th>Date of Birth</th><td class="ve-dob">—</td></tr>
                <tr><th>Birth Place</th><td class="ve-birth_place">—</td></tr>
                <tr><th>Civil Status</th><td class="ve-civil_status">—</td></tr>
                <tr><th>Religion</th><td class="ve-religion">—</td></tr>
                <tr><th>Spouse</th><td class="ve-spouse">—</td></tr>
                <tr><th>Occupation</th><td class="ve-occupation">—</td></tr>
                <tr><th>Children</th><td class="ve-name_of_children">—</td></tr>
                <tr><th>Children Birth Date</th><td class="ve-children_birth_date">—</td></tr>
                <tr><th>Father</th><td class="ve-father_name">—</td></tr>
                <tr><th>Father Occupation</th><td class="ve-father_occupation">—</td></tr>
                <tr><th>Mother</th><td class="ve-mother_name">—</td></tr>
                <tr><th>Mother Occupation</th><td class="ve-mother_occupation">—</td></tr>
                <tr><th>Languages Spoken</th><td class="ve-languages_spoken">—</td></tr>
                <tr><th>Current Address</th><td class="ve-current_address">—</td></tr>
                <tr><th>Permanent Address</th><td class="ve-permanent_address">—</td></tr>
              </tbody>
            </table>

            {{-- Work Info --}}
            <table class="table table-bordered mb-4">
              <thead class="table-light">
                <tr><th colspan="2" class="text-center">Work Information</th></tr>
              </thead>
              <tbody>
                <tr><th>Department</th><td class="ve-department">—</td></tr>
                <tr><th>Designation</th><td class="ve-designation">—</td></tr>
                <tr><th>Role</th><td class="ve-role">—</td></tr>
                <tr><th>Schedule</th><td class="ve-schedule">—</td></tr>
                <tr><th>Employment Type</th><td class="ve-employment_type">—</td></tr>
                <tr><th>Start–End</th><td class="ve-start_end">—</td></tr>
              </tbody>
            </table>

            {{-- Educational Background --}}
            <table class="table table-bordered mb-4">
              <thead class="table-light">
                <tr><th colspan="2" class="text-center">Educational Background</th></tr>
              </thead>
              <tbody>
                <tr><th>Elementary</th><td class="ve-elementary_school">—</td></tr>
                <tr><th>Year Graduated</th><td class="ve-elementary_year_graduated">—</td></tr>
                <tr><th>High School</th><td class="ve-high_school">—</td></tr>
                <tr><th>Year Graduated</th><td class="ve-high_school_year_graduated">—</td></tr>
                <tr><th>College</th><td class="ve-college">—</td></tr>
                <tr><th>Year Graduated</th><td class="ve-college_year_graduated">—</td></tr>
                <tr><th>Degree</th><td class="ve-degree_received">—</td></tr>
                <tr><th>Special Skills</th><td class="ve-special_skills">—</td></tr>
              </tbody>
            </table>

            {{-- Certifications --}}
            <table class="table table-bordered mb-4">
              <thead class="table-light">
                <tr><th colspan="2" class="text-center">Certifications & IDs</th></tr>
              </thead>
              <tbody>
                <tr><th>SSS No.</th><td class="ve-sss_no">—</td></tr>
                <tr><th>TIN No.</th><td class="ve-tin_no">—</td></tr>
                <tr><th>Pag-IBIG ID</th><td class="ve-pagibig_id_no">—</td></tr>
                <tr><th>PhilHealth No.</th><td class="ve-philhealth_tin_id_no">—</td></tr>
                <tr><th>GSIS ID</th><td class="ve-gsis_id_no">—</td></tr>
                <tr><th>Agency Emp. No.</th><td class="ve-agency_employee_no">—</td></tr>
              </tbody>
            </table>
          </div>

          {{-- RIGHT COLUMN --}}
          <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
              <div class="card-header fw-semibold">Documents & Profile</div>
              <div class="card-body">
                {{-- Profile Picture --}}
                <div class="text-center mb-4">
                  <div class="border rounded bg-light mx-auto mb-2"
                       style="width:180px;height:180px;overflow:hidden;">
                    <img id="ve-profile-img" class="w-100 h-100" style="object-fit:cover;display:none;">
                    <div id="ve-profile-empty"
                         class="d-flex align-items-center justify-content-center h-100 text-muted small">
                      No image
                    </div>
                  </div>
                  <a id="ve-profile-link" href="#" target="_blank"
                     class="d-none small text-decoration-none">
                    <i class="bi bi-box-arrow-up-right"></i> View Full Image
                  </a>
                </div>

                {{-- Resume --}}
                <div class="mb-3">
                  <label class="fw-semibold d-block">Resume</label>
                  <div class="border rounded p-2 bg-light small" id="ve-resume-block">No file</div>
                </div>

                {{-- MDRs --}}
                <div class="mb-3">
                  <label class="fw-semibold d-block">MDRs</label>
                  <ul class="list-unstyled small mb-0">
                    <li id="ve-mdr-ph">PhilHealth: —</li>
                    <li id="ve-mdr-sss">SSS: —</li>
                    <li id="ve-mdr-pagibig">Pag-IBIG: —</li>
                  </ul>
                </div>

                {{-- Medical Docs --}}
                <div>
                  <label class="fw-semibold d-block">Medical Documents</label>
                  <ul id="ve-medical-list" class="list-unstyled small mb-0">
                    <li class="text-muted">—</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('viewEmployeeModal');
  const defaultPhoto = "{{ asset('images/default-profile.png') }}";

  const setText = (cls, val) => {
    const el = modal.querySelector(cls);
    if (!el) return;
    const clean = (val === null || val === undefined || val === '' || val === 'null') ? '—' : String(val);
    el.textContent = clean;
  };

  modal.addEventListener('show.bs.modal', e => {
    const btn = e.relatedTarget;
    if (!btn) return;
    let emp = {};
    try { emp = JSON.parse(btn.getAttribute('data-employee') || '{}'); } catch {}

    // PERSONAL + WORK
    Object.keys(emp).forEach(k => setText(`.ve-${k}`, emp[k]));

    // DOB
    const dobField = modal.querySelector('.ve-dob');
    if (dobField) dobField.textContent = emp.dob ? new Date(emp.dob).toISOString().split('T')[0] : '—';

    // Address
    const curr = [emp.current_street_address, emp.current_barangay, emp.current_city, emp.current_province, emp.current_postal_code].filter(Boolean).join(', ');
    setText('.ve-current_address', curr);

    // ✅ Profile Picture
    const pImg = modal.querySelector('#ve-profile-img');
    const pEmpty = modal.querySelector('#ve-profile-empty');
    const pLink = modal.querySelector('#ve-profile-link');
    const photoUrl = emp.profile_picture_url || defaultPhoto;

    if (emp.profile_picture_url) {
      pImg.src = photoUrl;
      pImg.style.display = 'block';
      pEmpty.style.display = 'none';
      pLink.href = photoUrl;
      pLink.classList.remove('d-none');
    } else {
      pImg.src = defaultPhoto;
      pImg.style.display = 'block';
      pEmpty.style.display = 'none';
      pLink.classList.add('d-none');
    }

    // ✅ Resume
    const resumeBlock = modal.querySelector('#ve-resume-block');
    if (emp.resume_url) {
      resumeBlock.innerHTML = `<a href="${emp.resume_url}" target="_blank"><i class="bi bi-download"></i> Open Resume</a>`;
    } else {
      resumeBlock.textContent = '—';
    }

    // ✅ MDRs
    [['ph','mdr_philhealth_url'],['sss','mdr_sss_url'],['pagibig','mdr_pagibig_url']].forEach(([k,f]) => {
      const el = modal.querySelector(`#ve-mdr-${k}`);
      if (emp[f]) el.innerHTML = `<a href="${emp[f]}" target="_blank">Open</a>`;
      else el.textContent = el.textContent.split(':')[0] + ': —';
    });

    // ✅ Medical Docs
    const medList = modal.querySelector('#ve-medical-list');
    medList.innerHTML = '';
    if (Array.isArray(emp.medical_documents_urls) && emp.medical_documents_urls.length) {
      emp.medical_documents_urls.forEach((url, i) => {
        medList.insertAdjacentHTML('beforeend', `<li><a href="${url}" target="_blank">Document ${i+1}</a></li>`);
      });
    } else {
      medList.innerHTML = '<li class="text-muted">—</li>';
    }
  });
});
</script>
@endpush

<style>
#viewEmployeeModal th { width: 35%; background:#f8f9fa; font-weight:600; }
#viewEmployeeModal td { width: 65%; }
#viewEmployeeModal table { table-layout: fixed; word-break: break-word; }
</style>
