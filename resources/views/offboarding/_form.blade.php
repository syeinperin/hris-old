@php
  $o = $offboarding;
@endphp

<div class="row g-3">
  <div class="col-md-6 form-floating">
    <select name="employee_id" class="form-select" required {{ isset($o) && $o->isFinal() ? 'disabled' : '' }}>
      <option value="" disabled {{ empty($o?->employee_id) ? 'selected' : '' }}>— Select Employee —</option>
      @foreach($employees as $e)
        <option value="{{ $e->id }}" {{ (int)old('employee_id', $o->employee_id ?? 0) === (int)$e->id ? 'selected' : '' }}>
          {{ $e->employee_code }} — {{ $e->name }}
        </option>
      @endforeach
    </select>
    <label>Employee *</label>
  </div>

  <div class="col-md-3 form-floating">
    <select name="type" class="form-select" required>
      @php $type = old('type', $o->type ?? 'resignation'); @endphp
      @foreach(['resignation','termination','endo','retirement','other'] as $t)
        <option value="{{ $t }}" {{ $type === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
      @endforeach
    </select>
    <label>Type *</label>
  </div>

  <div class="col-md-3 form-floating">
    @php $eff = old('effective_date', optional($o?->effective_date)->format('Y-m-d')); @endphp
    <input type="date" name="effective_date" class="form-control" value="{{ $eff }}">
    <label>Effective Date</label>
  </div>

  @if(($mode ?? '') === 'edit')
    <div class="col-md-4 form-floating">
      @php $status = old('status', $o->status ?? 'draft'); @endphp
      <select name="status" class="form-select" required {{ $o->isFinal() ? 'disabled' : '' }}>
        @foreach(['draft','pending_clearance','scheduled','awaiting_approvals','completed','cancelled'] as $s)
          <option value="{{ $s }}" {{ $status === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
        @endforeach
      </select>
      <label>Status *</label>
    </div>
  @endif

  <div class="col-md-8 form-floating">
    <input type="text" name="reason" class="form-control" placeholder="Reason"
           value="{{ old('reason', $o->reason ?? '') }}">
    <label>Reason</label>
  </div>

  <div class="col-md-4 form-floating">
    @php $acc = old('allow_portal_access_until', optional($o?->allow_portal_access_until)->format('Y-m-d')); @endphp
    <input type="date" name="allow_portal_access_until" class="form-control" value="{{ $acc }}">
    <label>Allow Portal Access Until</label>
  </div>

  <div class="col-12">
    <div class="form-check">
      <input class="form-check-input" type="checkbox" value="1" id="assetRet" name="company_asset_returned"
        {{ old('company_asset_returned', $o->company_asset_returned ?? false) ? 'checked' : '' }}>
      <label class="form-check-label" for="assetRet">
        All company assets returned
      </label>
    </div>
  </div>

  <div class="col-12">
    <label class="form-label fw-semibold">Notes</label>
    <textarea name="separation_notes" class="form-control" rows="4" placeholder="Optional notes...">{{ old('separation_notes', $o->separation_notes ?? '') }}</textarea>
  </div>
</div>
