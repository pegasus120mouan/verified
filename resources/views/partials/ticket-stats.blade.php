@php
  $stats = $stats ?? $ticketStats ?? ['total' => 0, 'non_soldes' => 0, 'soldes' => 0];
@endphp

<div class="row g-3 mb-4 app-ticket-stats">
  <div class="col-md-4">
    <div class="rounded-3 p-4 h-100 shadow-sm" style="background:#00cfe8;color:#fff;">
      <div style="font-size:2.25rem;font-weight:700;line-height:1.1;color:#fff;">{{ number_format($stats['total'] ?? 0, 0, ',', ' ') }}</div>
      <p class="mb-0 mt-2" style="font-size:0.9375rem;color:#fff;">Nombre de tickets vérifiés — <strong>Total</strong></p>
    </div>
  </div>
  <div class="col-md-4">
    <div class="rounded-3 p-4 h-100 shadow-sm" style="background:#ff9f43;color:#fff;">
      <div style="font-size:2.25rem;font-weight:700;line-height:1.1;color:#fff;">{{ number_format($stats['non_soldes'] ?? 0, 0, ',', ' ') }}</div>
      <p class="mb-0 mt-2" style="font-size:0.9375rem;color:#fff;">Tickets <strong>non soldés</strong></p>
    </div>
  </div>
  <div class="col-md-4">
    <div class="rounded-3 p-4 h-100 shadow-sm" style="background:#28c76f;color:#fff;">
      <div style="font-size:2.25rem;font-weight:700;line-height:1.1;color:#fff;">{{ number_format($stats['soldes'] ?? 0, 0, ',', ' ') }}</div>
      <p class="mb-0 mt-2" style="font-size:0.9375rem;color:#fff;">Tickets <strong>soldés</strong></p>
    </div>
  </div>
</div>
