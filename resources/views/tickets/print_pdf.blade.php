<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <title>Mes tickets</title>
 <!-- use Spatie\LaravelPdf\Facades\Pdf;-->
  <style>
    body {
      font-family: DejaVu Sans, sans-serif;
      font-size: 9px;
      color: #222;
      margin: 12px;
      position: relative;
    }
    .pdf-filigrane {
      position: fixed;
      top: 30%;
      left: 15%;
      width: 70%;
      text-align: center;
      opacity: 0.1;
      z-index: 0;
      pointer-events: none;
    }
    .pdf-filigrane img {
      width: 100%;
      max-width: 300px;
      height: auto;
      margin: 0 auto;
    }
    .pdf-content {
      position: relative;
      z-index: 1;
    }
    .pdf-entete-logo {
      text-align: left;
      margin-bottom: 10px;
    }
    .pdf-entete-logo img {
      display: block;
      width: 100px;
      height: auto;
      max-height: 100px;
    }
    h1 { font-size: 14px; margin: 0 0 8px; }
    .meta { font-size: 8.5px; color: #444; margin-bottom: 10px; }
    .meta p { margin: 2px 0; }
    .pdf-usine-titre {
      text-align: center;
      font-size: 11px;
      font-weight: bold;
      text-transform: uppercase;
      margin: 16px 0 8px;
    }
    .pdf-usine-bloc { margin-bottom: 18px; }
    .pdf-usine-bloc:first-of-type .pdf-usine-titre { margin-top: 4px; }
    table { width: 100%; border-collapse: collapse; font-size: 8px; }
    th, td { border: 1px solid #999; padding: 4px 5px; text-align: left; vertical-align: top; }
    th { background: #eee; font-weight: bold; }
    td.num, th.num { text-align: right; }
    .sous-total-row td, .total-row td { font-weight: bold; background: #f0f0f0; }
    .sous-total-label { text-align: right; }
    .total-general { margin-top: 14px; }
    .pdf-verificateur {
      margin-top: 20px;
      padding-top: 10px;
      border-top: 1px solid #bbb;
      font-size: 8.5px;
    }
  </style>
</head>
<body>
  @php
    $logoCandidates = [
      public_path('img/logo/logo.png'),
      public_path('img/logo.png'),
    ];
    $logoPath = null;
    foreach ($logoCandidates as $candidate) {
      if (is_readable($candidate)) {
        $logoPath = $candidate;
        break;
      }
    }
    $logoSrc = null;
    if ($logoPath !== null) {
      $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
      $mime = match ($ext) {
        'jpg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        default => 'image/png',
      };
      $logoSrc = 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($logoPath));
    }
  @endphp
  @if ($logoSrc)
    <div class="pdf-filigrane" aria-hidden="true">
      <img src="{{ $logoSrc }}" alt="" />
    </div>
  @endif
  <div class="pdf-content">
  @if ($logoSrc)
    <div class="pdf-entete-logo">
      <img src="{{ $logoSrc }}" alt="Logo" />
    </div>
  @endif
  <h1>Tickets Verifiés</h1>
  <div class="meta">
    @if($filterAgentLabel)
      <p><strong>Agent :</strong> {{ $filterAgentLabel }}</p>
    @else
      <p><strong>Agent :</strong> tous</p>
    @endif
    @if($filterDateDebut || $filterDateFin)
      <p>
        <strong>Date réception :</strong>
        @if($filterDateDebut && $filterDateFin)
          du {{ $filterDateDebut }} au {{ $filterDateFin }}
        @elseif($filterDateDebut)
          à partir du {{ $filterDateDebut }}
        @else
          jusqu'au {{ $filterDateFin }}
        @endif
      </p>
    @else
      <p><strong>Date réception :</strong> toutes</p>
    @endif
  </div>

  @php
    $hideAgentColumn = filled($filterAgentLabel);
    $labelColspan = $hideAgentColumn ? 3 : 4;
    $grouped = $tickets->groupBy('id_usine');
    $sortedIds = $grouped->keys()->sortBy(
      fn ($idUsine) => mb_strtoupper((string) ($usineNames[(int) $idUsine] ?? ''))
    );
    $ticketsByUsine = $sortedIds->mapWithKeys(fn ($idUsine) => [$idUsine => $grouped->get($idUsine)]);
    $totalMontant = $tickets->sum(fn ($t) => $t->poids !== null ? (float) $t->poids * (float) $t->prix_unitaire : 0.0);
    $totalPoids = $tickets->sum(fn ($t) => (float) ($t->poids ?? 0));
  @endphp

  @if ($tickets->isEmpty())
    <p style="text-align:center;color:#666;">Aucun ticket pour ces critères.</p>
  @else
    @foreach ($ticketsByUsine as $idUsine => $groupTickets)
      @php
        $nomUsine = mb_strtoupper((string) ($usineNames[(int) $idUsine] ?? ('Usine #'.$idUsine)));
        $nbTickets = $groupTickets->count();
        $sousPoids = $groupTickets->sum(fn ($t) => (float) ($t->poids ?? 0));
        $sousMontant = $groupTickets->sum(fn ($t) => $t->poids !== null ? (float) $t->poids * (float) $t->prix_unitaire : 0.0);
      @endphp
      <div class="pdf-usine-bloc">
        <div class="pdf-usine-titre">{{ $nomUsine }}</div>
        <table>
          <thead>
            <tr>
              <th>Date réception</th>
              <th>Date ticket</th>
              <th>N° ticket</th>
              @unless ($hideAgentColumn)
              <th>Agent</th>
              @endunless
              <th class="num">Poids (kg)</th>
              <th class="num">Prix U</th>
              <th class="num">Montant du ticket</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($groupTickets as $ticket)
              <tr>
                <td>{{ $ticket->created_at?->format('d/m/Y') ?? '—' }}</td>
                <td>{{ $ticket->date_ticket?->format('d/m/Y') ?? '—' }}</td>
                <td>{{ $ticket->numero_ticket }}</td>
                @unless ($hideAgentColumn)
                <td>{{ $agentNames[$ticket->id_agent] ?? '—' }}</td>
                @endunless
                <td class="num">
                  @if ($ticket->poids !== null)
                    {{ number_format((float) $ticket->poids, 0, ',', ' ') }}
                  @else
                    —
                  @endif
                </td>
                <td class="num">{{ number_format(round((float) $ticket->prix_unitaire), 0, ',', ' ') }}</td>
                <td class="num">
                  @if ($ticket->poids !== null)
                    {{ number_format(round((float) $ticket->poids * (float) $ticket->prix_unitaire), 0, ',', ' ') }}
                  @else
                    —
                  @endif
                </td>
              </tr>
            @endforeach
            <tr class="sous-total-row">
              <td colspan="{{ $labelColspan }}" class="sous-total-label">Sous-total {{ $nomUsine }} ({{ $nbTickets }} {{ $nbTickets > 1 ? 'tickets' : 'ticket' }})</td>
              <td class="num">{{ number_format(round($sousPoids), 0, ',', ' ') }}</td>
              <td class="num">—</td>
              <td class="num">{{ number_format(round($sousMontant), 0, ',', ' ') }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    @endforeach

    <table class="total-general">
      <tbody>
        <tr class="total-row">
          <td colspan="{{ $labelColspan }}" class="sous-total-label">Total général ({{ $tickets->count() }} {{ $tickets->count() > 1 ? 'tickets' : 'ticket' }})</td>
          <td class="num">{{ number_format(round($totalPoids), 0, ',', ' ') }}</td>
          <td class="num">—</td>
          <td class="num">{{ number_format(round($totalMontant), 0, ',', ' ') }}</td>
        </tr>
      </tbody>
    </table>
  @endif

  <p class="pdf-verificateur"><strong>Vérificateur :</strong> {{ $verifierName }}</p>
  </div>
</body>
</html>
