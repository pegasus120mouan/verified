@php
  $stats = $stats ?? $agentStats ?? ['total' => 0, 'page_count' => 0, 'current_page' => 1, 'last_page' => 1];
@endphp

<div class="row g-3 mb-4 app-agent-stats">
  <div class="col-md-4">
    <div class="rounded-3 p-4 h-100 shadow-sm" style="background:#00cfe8;color:#fff;">
      <div style="font-size:2.25rem;font-weight:700;line-height:1.1;color:#fff;">{{ number_format($stats['total'] ?? 0, 0, ',', ' ') }}</div>
      <p class="mb-0 mt-2" style="font-size:0.9375rem;color:#fff;">Nombre d'agents — <strong>Total</strong></p>
    </div>
  </div>
  <div class="col-md-4">
    <div class="rounded-3 p-4 h-100 shadow-sm" style="background:#ff9f43;color:#fff;">
      <div style="font-size:2.25rem;font-weight:700;line-height:1.1;color:#fff;">{{ number_format($stats['page_count'] ?? 0, 0, ',', ' ') }}</div>
      <p class="mb-0 mt-2" style="font-size:0.9375rem;color:#fff;">Agents <strong>sur cette page</strong></p>
    </div>
  </div>
  <div class="col-md-4">
    <div class="rounded-3 p-4 h-100 shadow-sm" style="background:#28c76f;color:#fff;">
      <div style="font-size:2.25rem;font-weight:700;line-height:1.1;color:#fff;">{{ number_format($stats['current_page'] ?? 1, 0, ',', ' ') }}</div>
      <p class="mb-0 mt-2" style="font-size:0.9375rem;color:#fff;">Page <strong>{{ $stats['current_page'] ?? 1 }}</strong> / {{ max(1, $stats['last_page'] ?? 1) }}</p>
    </div>
  </div>
</div>
