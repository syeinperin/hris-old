{{-- File: resources/views/partials/sidebar.blade.php --}}
@php
  use Illuminate\Support\Facades\Route as RouteFacade;

  // ✅ Fix: Prevent redeclaration error if sidebar loads multiple times
  if (!function_exists('menuLinkMeta')) {
      /**
       * Build a link target for a route name if it exists.
       * Returns ['url' => string, 'exists' => bool, 'active' => bool]
       */
      function menuLinkMeta($routeName) {
          $exists = $routeName && RouteFacade::has($routeName);
          $url    = $exists ? route($routeName) : '#';
          $active = $exists && request()->routeIs($routeName.'*');
          return compact('url','exists','active');
      }
  }
@endphp

<nav class="nav flex-column">

  {{-- Brand / Logo --}}
  <a class="sidebar-brand" href="{{ route('dashboard') }}">
    <img src="{{ asset('images/asiatex-logo.png') }}" alt="ASIATEX Logo" class="brand-logo">
  </a>

  {{-- Dynamic menu items --}}
  @foreach($menuItems as $item)
    @php
      $meta = menuLinkMeta($item->route ?? '');
    @endphp

    <a
      class="nav-link {{ $meta['active'] ? 'active' : '' }} {{ $meta['exists'] ? '' : 'disabled text-muted' }}"
      href="{{ $meta['url'] }}"
      @unless($meta['exists'])
        title="Route '{{ $item->route }}' is not defined"
        aria-disabled="true"
      @endunless
    >
      @if(!empty($item->icon))
        <i class="bi bi-{{ $item->icon }} me-2"></i>
      @endif
      {{ $item->title }}
    </a>

    {{-- Sub-items --}}
    @if(!empty($item->children) && $item->children->isNotEmpty())
      @foreach($item->children as $child)
        @php
          $cmeta = menuLinkMeta($child->route ?? '');
        @endphp

        <a
          class="nav-link ps-4 {{ $cmeta['active'] ? 'active' : '' }} {{ $cmeta['exists'] ? '' : 'disabled text-muted' }}"
          href="{{ $cmeta['url'] }}"
          @unless($cmeta['exists'])
            title="Route '{{ $child->route }}' is not defined"
            aria-disabled="true"
          @endunless
        >
          @if(!empty($child->icon))
            <i class="bi bi-{{ $child->icon }} me-2"></i>
          @endif
          {{ $child->title }}
        </a>
      @endforeach
    @endif
  @endforeach

</nav>
