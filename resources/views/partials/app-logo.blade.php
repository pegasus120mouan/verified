@php
    $logoHeight = $logoHeight ?? 72;
@endphp
<img
    src="{{ asset('img/logo/logo.png') }}"
    alt="{{ $logoAlt ?? 'Unipalm' }}"
    class="app-brand-logo-img {{ $logoClass ?? '' }}"
    style="max-height: {{ (int) $logoHeight }}px; width: auto; height: auto;"
/>
