{{-- resources/views/employees/partials/edit-modal.blade.php --}}
<div class="modal fade" id="editEmployeeModal" tabindex="-1" aria-labelledby="editEmployeeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content border-0 shadow-sm">

      <form id="editEmployeeForm" method="POST" enctype="multipart/form-data" novalidate onsubmit="return handleEditSubmit(event)">
        @csrf
        <input type="hidden" name="_method" value="PUT">

        {{-- HEADER --}}
        <div class="modal-header bg-white border-bottom">
          <h5 class="modal-title">
            <i class="bi bi-pencil-square me-2"></i>
            Edit <span class="js-emp-code">Employee</span>
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        {{-- BODY --}}
        <div class="modal-body p-0" style="max-height:calc(100vh - 200px);overflow-y:auto;">
          {{-- Tab Navigation (aligned to create.blade.php) --}}
          <nav class="tabs border-bottom px-3 pt-3" id="editTabs">
            <a href="#tab-edit-personal" class="active">Personal</a>
            <a href="#tab-edit-account">Account</a>
            <a href="#tab-edit-work">Work</a>
            <a href="#tab-edit-benefits">Benefits</a>
            <a href="#tab-edit-education">Education</a>
            <a href="#tab-edit-employment">Employment History</a>
            <a href="#tab-edit-refs">Character References</a>
            <a href="#tab-edit-docs">Certificates & Docs</a>
          </nav>

          {{-- Tab Panels --}}
          <div class="p-4 tab-panels">

            {{-- PERSONAL --}}
            <section id="tab-edit-personal" class="tab-panel">
              <div class="form-grid">
                <div class="form-floating span-4">
                  <input type="text" name="first_name" class="form-control" required>
                  <label>First Name *</label>
                </div>
                <div class="form-floating span-4">
                  <input type="text" name="middle_name" class="form-control">
                  <label>Middle Name</label>
                </div>
                <div class="form-floating span-4">
                  <input type="text" name="last_name" class="form-control" required>
                  <label>Last Name *</label>
                </div>

                <div class="form-floating span-4">
                  <select name="gender" class="form-select" required>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                  </select>
                  <label>Gender *</label>
                </div>
                <div class="form-floating span-4">
                  <input type="date" name="dob" class="form-control" required>
                  <label>Date of Birth *</label>
                </div>
                <div class="form-floating span-4">
                  <input type="text" name="birth_place" class="form-control">
                  <label>Birth Place</label>
                </div>

                   {{-- ✅ Profile Picture --}}
                <div class="span-12 mb-3 d-flex align-items-center gap-3">
                  <div class="text-center">
                    <img id="empEditPhotoPreview" src="{{ asset('images/default-profile.png') }}"
                         class="rounded-circle border" width="100" height="100"
                         style="object-fit:cover">
                  </div>
                  <div class="flex-fill">
                    <label class="form-label fw-semibold">Profile Picture</label>
                    <input type="file" name="profile_picture" accept="image/*" class="form-control">
                    <div class="form-text">
                      Current: <a id="em-cur-photo" href="#" target="_blank" class="d-none">View</a>
                      <span id="em-cur-photo-none" class="text-muted">none</span>
                    </div>
                  </div>
                </div>

                <div class="form-floating span-4">
                  <select name="civil_status" class="form-select">
                    <option value="">—</option>
                    <option value="single">Single</option>
                    <option value="married">Married</option>
                    <option value="widowed">Widowed</option>
                    <option value="separated">Separated</option>
                    <option value="other">Other</option>
                  </select>
                  <label>Civil Status</label>
                </div>
                <div class="form-floating span-4">
                  <input type="text" name="religion" class="form-control">
                  <label>Religion</label>
                </div>

                <div class="form-floating span-4">
                  <input type="text" name="languages_spoken" class="form-control">
                  <label>Languages Spoken</label>
                </div>

                <div class="form-floating span-6">
                  <input type="text" name="father_name" class="form-control">
                  <label>Father’s Name</label>
                </div>
                <div class="form-floating span-6">
                  <input type="text" name="mother_name" class="form-control">
                  <label>Mother’s Name</label>
                </div>

                <div class="form-floating span-6">
                  <input type="text" name="emergency_contact_name" class="form-control">
                  <label>Emergency Contact</label>
                </div>
                <div class="form-floating span-6">
                  <input type="text" name="emergency_contact_phone" class="form-control">
                  <label>Emergency Number</label>
                </div>
              </div>
            </section>

            {{-- ACCOUNT --}}
            <section id="tab-edit-account" class="tab-panel" hidden>
              <div class="form-grid">
                <div class="form-floating span-4">
                  <input type="email" name="email" class="form-control" required>
                  <label>Email *</label>
                </div>
                <div class="form-floating span-4">
                  <select name="role" class="form-select" required>
                    @foreach($roles as $r)
                      <option value="{{ $r }}">{{ ucfirst($r) }}</option>
                    @endforeach
                  </select>
                  <label>Role *</label>
                </div>
                <div class="form-floating span-4">
                  <select name="status" class="form-select" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="pending">Pending</option>
                  </select>
                  <label>Status *</label>
                </div>
              </div>
            </section>

            {{-- WORK --}}
            <section id="tab-edit-work" class="tab-panel" hidden>
              <div class="form-grid">
                <div class="form-floating span-4">
                  <select name="department_id" class="form-select" required>
                    @foreach($departments as $id => $name)
                      <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                  </select>
                  <label>Department *</label>
                </div>
                <div class="form-floating span-4">
                  <select name="designation_id" class="form-select" required>
                    @foreach($designations as $id => $name)
                      <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                  </select>
                  <label>Designation *</label>
                </div>
                <div class="form-floating span-4">
                  <select name="employment_type" id="employment_type_edit" class="form-select" required>
                    <option value="probationary">Probationary</option>
                    <option value="regular">Regular</option>
                    <option value="fixed-term">Fixed-term</option>
                    <option value="probationary">Probationary</option>
                  </select>
                  <label>Employment Type *</label>
                </div>

                <div class="form-floating span-4">
                  <input type="date" name="employment_start_date" id="employment_start_date_edit" class="form-control" required>
                  <label>Start Date *</label>
                </div>
                <div class="form-floating span-4">
                  <input type="date" name="employment_end_date" id="employment_end_date_edit" class="form-control">
                  <label id="endDateLabelEdit">End Date</label>
                </div>
              </div>
            </section>

            {{-- BENEFITS --}}
            <section id="tab-edit-benefits" class="tab-panel" hidden>
              <div class="form-grid">
                <div class="form-floating span-4">
                  <input type="text" name="sss_no" class="form-control">
                  <label>SSS No.</label>
                </div>
                <div class="form-floating span-4">
                  <input type="text" name="tin_no" class="form-control">
                  <label>TIN No.</label>
                </div>
                <div class="form-floating span-4">
                  <input type="text" name="philhealth_tin_id_no" class="form-control">
                  <label>PhilHealth No.</label>
                </div>
                <div class="form-floating span-4">
                  <input type="text" name="pagibig_id_no" class="form-control">
                  <label>Pag-IBIG ID</label>
                </div>
                <div class="form-floating span-4">
                  <input type="text" name="gsis_id_no" class="form-control">
                  <label>GSIS ID</label>
                </div>
                <div class="form-floating span-4">
                  <input type="text" name="agency_employee_no" class="form-control">
                  <label>Agency Employee No.</label>
                </div>
              </div>
            </section>

            {{-- EDUCATION --}}
            <section id="tab-edit-education" class="tab-panel" hidden>
              <div class="form-grid">
                <div class="span-12"><h6 class="fw-bold mb-2">🎓 Educational Background</h6></div>
                <div class="form-floating span-6">
                  <input type="text" name="elementary_school" class="form-control">
                  <label>Elementary School</label>
                </div>
                <div class="form-floating span-6">
                  <input type="text" name="high_school" class="form-control">
                  <label>High School</label>
                </div>
                <div class="form-floating span-6">
                  <input type="text" name="college" class="form-control">
                  <label>College</label>
                </div>
                <div class="form-floating span-6">
                  <input type="text" name="degree_received" class="form-control">
                  <label>Degree</label>
                </div>
                <div class="form-floating span-12">
                  <textarea name="special_skills" class="form-control" placeholder="Special Skills" style="height:100px"></textarea>
                  <label>Special Skills</label>
                </div>
              </div>
            </section>

            {{-- EMPLOYMENT HISTORY --}}
            <section id="tab-edit-employment" class="tab-panel" hidden>
              <div class="form-grid">
                <div class="form-floating span-5">
                  <input type="text" name="emp1_company" class="form-control">
                  <label>Company</label>
                </div>
                <div class="form-floating span-3">
                  <input type="text" name="emp1_position" class="form-control">
                  <label>Position</label>
                </div>
                <div class="form-floating span-2">
                  <input type="date" name="emp1_from" class="form-control">
                  <label>From</label>
                </div>
                <div class="form-floating span-2">
                  <input type="date" name="emp1_to" class="form-control">
                  <label>To</label>
                </div>
              </div>
            </section>

            {{-- REFERENCES --}}
            <section id="tab-edit-refs" class="tab-panel" hidden>
              <div class="form-grid">
                <div class="form-floating span-4">
                  <input type="text" name="char1_name" class="form-control">
                  <label>Name</label>
                </div>
                <div class="form-floating span-3">
                  <input type="text" name="char1_position" class="form-control">
                  <label>Position</label>
                </div>
                <div class="form-floating span-3">
                  <input type="text" name="char1_company" class="form-control">
                  <label>Company</label>
                </div>
                <div class="form-floating span-2">
                  <input type="text" name="char1_contact" class="form-control">
                  <label>Contact</label>
                </div>
              </div>
            </section>

           {{-- DOCUMENTS --}}
<section id="tab-edit-docs" class="tab-panel" hidden>
  <div class="form-grid">

    {{-- Resume --}}
    <div class="form-floating span-6">
      <input type="file" name="resume_file" class="form-control" accept=".pdf,.doc,.docx,image/*">
      <label>Resume</label>
      <div class="form-text">Current:
        <a id="em-cur-resume" href="#" target="_blank" class="d-none">Open</a>
        <span id="em-cur-resume-none" class="text-muted">none</span>
      </div>
    </div>

    {{-- MDR – PhilHealth --}}
    <div class="form-floating span-6">
      <input type="file" name="mdr_philhealth_file" class="form-control" accept=".pdf,image/*">
      <label>MDR – PhilHealth</label>
      <div class="form-text">Current:
        <a id="em-cur-philhealth" href="#" target="_blank" class="d-none">Open</a>
        <span id="em-cur-philhealth-none" class="text-muted">none</span>
      </div>
    </div>

    {{-- MDR – SSS --}}
    <div class="form-floating span-6">
      <input type="file" name="mdr_sss_file" class="form-control" accept=".pdf,image/*">
      <label>MDR – SSS</label>
      <div class="form-text">Current:
        <a id="em-cur-sss" href="#" target="_blank" class="d-none">Open</a>
        <span id="em-cur-sss-none" class="text-muted">none</span>
      </div>
    </div>

    {{-- MDR – Pag-IBIG --}}
    <div class="form-floating span-6">
      <input type="file" name="mdr_pagibig_file" class="form-control" accept=".pdf,image/*">
      <label>MDR – Pag-IBIG</label>
      <div class="form-text">Current:
        <a id="em-cur-pagibig" href="#" target="_blank" class="d-none">Open</a>
        <span id="em-cur-pagibig-none" class="text-muted">none</span>
      </div>
    </div>

    {{-- Medical Documents --}}
    <div class="span-12">
      <label class="fw-semibold d-block mb-1">Medical Documents</label>
      <input type="file" name="medical_documents[]" class="form-control" multiple accept=".pdf,image/*">
      <div class="form-text">
        You may upload multiple files (PDF or image formats).
        <br>
        Current:
        <div id="em-cur-medical" class="mt-1 small text-muted">none</div>
      </div>
    </div>

  </div>
</section>


        {{-- FOOTER --}}
        <div class="modal-footer bg-light border-top">
          <button type="submit" class="btn btn-success"><i class="bi bi-save2 me-1"></i> Update</button>
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const editModal = document.getElementById('editEmployeeModal');
  const form = editModal?.querySelector('#editEmployeeForm');
  const baseEmployeesUrl = @json(url('/employees'));

  editModal?.addEventListener('show.bs.modal', (evt) => {
    const btn = evt.relatedTarget;
    if (!btn) return;

    let emp = {};
    try { emp = JSON.parse(btn.getAttribute('data-employee') || '{}'); } catch {}

    // Form action + title
    form.action = `${baseEmployeesUrl}/${emp.id}`;
    editModal.querySelector('.js-emp-code').textContent = emp.employee_code || 'Employee';

    // Reset all inputs
    form.querySelectorAll('input, select, textarea').forEach(el => {
      if (el.name !== '_token' && el.name !== '_method') el.value = '';
    });

    // ✅ Populate values safely (skip file inputs)
    for (const [k, v] of Object.entries(emp)) {
      const el = form.querySelector(`[name="${k}"]`);
      if (!el) continue;
      if (el.type === 'file') continue; // 🟢 skip setting value for file inputs

      let val = v ?? '';

      // Convert date formats
      if (/\d{4}-\d{2}-\d{2}/.test(val) || val.includes('/')) {
        try {
          const d = new Date(val);
          if (!isNaN(d)) val = d.toISOString().split('T')[0];
        } catch {}
      }

      el.value = val;
    }

    // ✅ MDR links (PhilHealth, SSS, Pag-IBIG)
    const mdrFields = [
      ['philhealth', 'mdr_philhealth_file'],
      ['sss', 'mdr_sss_file'],
      ['pagibig', 'mdr_pagibig_file']
    ];
    mdrFields.forEach(([key, field]) => {
      const link = editModal.querySelector(`#em-cur-${key}`);
      const none = editModal.querySelector(`#em-cur-${key}-none`);
      if (!link || !none) return;
      if (emp[field]) {
        link.href = emp[field].startsWith('uploads')
          ? `/storage/${emp[field]}`
          : emp[field];
        link.classList.remove('d-none');
        none.classList.add('d-none');
      } else {
        link.classList.add('d-none');
        none.classList.remove('d-none');
      }
    });

    // ✅ Medical documents
    const medDiv = editModal.querySelector('#em-cur-medical');
    if (Array.isArray(emp.medical_documents) && emp.medical_documents.length) {
      medDiv.innerHTML = emp.medical_documents
        .map((path, i) => `<a href="/storage/${path}" target="_blank">Document ${i + 1}</a>`)
        .join(', ');
    } else {
      medDiv.textContent = 'none';
    }

    // ✅ Tab switching logic
    const tabsNav = editModal.querySelector('#editTabs');
    const panels = editModal.querySelectorAll('.tab-panel');

    function showTab(id) {
      panels.forEach(p => {
        const isActive = p.id === id;
        p.hidden = !isActive;
        p.style.display = isActive ? 'block' : 'none';
      });
    }

    tabsNav?.addEventListener('click', e => {
      const a = e.target.closest('a');
      if (!a) return;
      e.preventDefault();
      tabsNav.querySelectorAll('a').forEach(x => x.classList.remove('active'));
      a.classList.add('active');
      showTab(a.getAttribute('href').substring(1));
    });

    // Reset to first tab each time modal opens
    const firstTab = tabsNav.querySelector('a');
    if (firstTab) {
      tabsNav.querySelectorAll('a').forEach(x => x.classList.remove('active'));
      firstTab.classList.add('active');
      showTab(firstTab.getAttribute('href').substring(1));
    }

    // ✅ Auto-compute probationary end date
    const empTypeEl = editModal.querySelector('[name="employment_type"]');
    const startDateEl = editModal.querySelector('[name="employment_start_date"]');
    const endDateEl = editModal.querySelector('[name="employment_end_date"]');
    const endDateLabel = document.getElementById('endDateLabelEdit');

    function computeEndDate() {
      const empType = empTypeEl?.value?.toLowerCase();
      const startVal = startDateEl?.value;
      if (empType === 'probationary' && startVal) {
        const start = new Date(startVal);
        if (!isNaN(start)) {
          const end = new Date(start);
          end.setMonth(end.getMonth() + 6);
          endDateEl.value = end.toISOString().split('T')[0];
        }
        endDateEl.readOnly = true;
        endDateEl.classList.add('bg-light');
        endDateLabel.textContent = 'End Date (auto 6 months)';
      } else {
        endDateEl.readOnly = false;
        endDateEl.classList.remove('bg-light');
        endDateLabel.textContent = 'End Date';
      }
    }

    empTypeEl?.addEventListener('change', computeEndDate);
    startDateEl?.addEventListener('change', computeEndDate);
    computeEndDate();
  });

  // ✅ AJAX submit handler
  window.handleEditSubmit = async (event) => {
    event.preventDefault();
    const btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Saving...';

    const formData = new FormData(form);

    try {
      const res = await fetch(form.action, {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body: formData
      });

      const data = await res.json().catch(() => ({}));

      if (res.status === 422 && data.errors) {
        form.querySelectorAll('.invalid-feedback').forEach(e => e.remove());
        Object.entries(data.errors).forEach(([k, v]) => {
          const el = form.querySelector(`[name="${k}"]`);
          if (el) {
            el.classList.add('is-invalid');
            const fb = document.createElement('div');
            fb.className = 'invalid-feedback d-block';
            fb.textContent = v.join(', ');
            el.closest('.form-floating')?.appendChild(fb);
          }
        });
        showModalAlert('danger', 'Please correct the highlighted errors.');
        return;
      }

      if (res.ok) {
        showModalAlert('success', 'Employee updated successfully!');
        setTimeout(() => {
          bootstrap.Modal.getInstance(editModal)?.hide();
          location.reload();
        }, 1000);
      } else {
        showModalAlert('danger', 'Server error. Try again.');
      }
    } catch (err) {
      console.error(err);
      showModalAlert('danger', err.message);
    } finally {
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-save2 me-1"></i> Update';
    }
  };

  function showModalAlert(type, msg) {
    const box = document.createElement('div');
    box.className = `alert alert-${type} mt-2`;
    box.textContent = msg;
    form.prepend(box);
    setTimeout(() => box.remove(), 2500);
  }
});
</script>
