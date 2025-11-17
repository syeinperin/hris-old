@props([
  'action',
  'placeholder'   => 'Search…',
  'filters'       => [],          // ['field' => ['val'=>'Label', ...]]
  'showDateRange' => false,       // ← enable start/end inputs when needed
  'startName'     => 'start_date',
  'endName'       => 'end_date',
  'showReset'     => true,
])

@php
  $qName      = 'search';
  $qValue     = request($qName);
  $startValue = request($startName);
  $endValue   = request($endName);

  // Preserve other query params (except what we control)
  $exclude    = array_merge([$qName, $startName, $endName, 'page']);
  $preserve   = collect(request()->except($exclude));
@endphp

<form action="{{ $action }}" method="GET" class="filter-bar mb-3">
  <div class="row g-2 align-items-end">

    {{-- Free-text search --}}
    <div class="col">
      <label class="form-label">Search</label>
      <input type="text"
             name="{{ $qName }}"
             value="{{ old($qName, $qValue) }}"
             class="form-control"
             placeholder="{{ $placeholder }}">
    </div>

    {{-- Optional: date range --}}
    @if($showDateRange)
      <div class="col-auto">
        <label class="form-label">Start</label>
        <input type="date"
               name="{{ $startName }}"
               value="{{ old($startName, $startValue) }}"
               class="form-control">
      </div>
      <div class="col-auto">
        <label class="form-label">End</label>
        <input type="date"
               name="{{ $endName }}"
               value="{{ old($endName, $endValue) }}"
               class="form-control">
      </div>
    @endif

    {{-- Optional select-filters --}}
    @foreach($filters as $field => $options)
      <div class="col-auto">
        <label class="form-label">
          {{ \Illuminate\Support\Str::title(str_replace('_',' ',$field)) }}
        </label>
        <select name="{{ $field }}" class="form-select">
          <option value="">All {{ \Illuminate\Support\Str::title(str_replace('_',' ',$field)) }}</option>
          @foreach($options as $val => $label)
            <option value="{{ $val }}" {{ request($field)==(string)$val ? 'selected':'' }}>
              {{ $label }}
            </option>
          @endforeach
        </select>
      </div>
    @endforeach

    {{-- Submit + Reset --}}
    <div class="col-auto d-flex gap-2">
      @foreach($preserve as $k => $v)
        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
      @endforeach

      <button type="submit" class="btn btn-primary">
        <i class="bi bi-search me-1"></i> Search
      </button>

      @if($showReset)
        <a href="{{ $action }}" class="btn btn-outline-secondary">
          <i class="bi bi-x-circle me-1"></i> Reset
        </a>
      @endif
    </div>
  </div>
</form>
