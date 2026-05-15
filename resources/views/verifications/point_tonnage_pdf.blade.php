<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <title>Point tonnage</title>
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
      width: 90px;
      height: auto;
      max-height: 90px;
    }
    h1 { font-size: 14px; margin: 0 0 8px; }
    .meta { font-size: 8.5px; color: #444; margin-bottom: 10px; }
    .meta p { margin: 2px 0; }
    .pdf-agent-titre {
      text-align: center;
      font-size: 10px;
      font-weight: bold;
      text-transform: uppercase;
      margin: 14px 0 6px;
    }
    .pdf-agent-bloc { margin-bottom: 14px; }
    .pdf-agent-bloc:first-of-type .pdf-agent-titre { margin-top: 2px; }
    table { width: 100%; border-collapse: collapse; font-size: 8px; }
    th, td { border: 1px solid #999; padding: 4px 5px; text-align: left; vertical-align: top; }
    th { background: #eee; font-weight: bold; }
    td.num, th.num { text-align: right; }
    .sous-total-row td, .total-row td { font-weight: bold; background: #f0f0f0; }
    .sous-total-label { text-align: right; }
    .total-general { margin-top: 12px; }
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
  <h1>Point tonnage — {{ $nomUsine }}</h1>
  <div class="meta">
    <p><strong>Usine :</strong> {{ $nomUsine }} (réf. #{{ $idUsine }})</p>
    @if ($filterDateDebut || $filterDateFin)
      <p>
        <strong>Période (date de réception / enregistrement) :</strong>
        @if ($filterDateDebut && $filterDateFin)
          du {{ $filterDateDebut }} au {{ $filterDateFin }}
        @elseif ($filterDateDebut)
          à partir du {{ $filterDateDebut }}
        @else
          jusqu'au {{ $filterDateFin }}
        @endif
      </p>
    @else
      <p><strong>Période :</strong> toutes les dates</p>
    @endif
  </div>

  @php
    $groupedByAgent = $tickets->groupBy('id_agent');
    $sortedAgentIds = $groupedByAgent->keys()->sortBy(
      fn ($idAgent) => mb_strtoupper((string) ($agentNames[(int) $idAgent] ?? ''))
    );
    $ticketsByAgent = $sortedAgentIds->mapWithKeys(fn ($idAgent) => [$idAgent => $groupedByAgent->get($idAgent)]);
  @endphp

  @if ($tickets->isEmpty())
    <p style="text-align:center;color:#666;">Aucun ticket sur cette période.</p>
  @else
    @foreach ($ticketsByAgent as $idAgent => $groupTickets)
      @php
        $nomAgent = (string) ($agentNames[(int) $idAgent] ?? ('Agent #'.$idAgent));
        $nomAgentTitre = mb_strtoupper($nomAgent);
        $nbAgent = $groupTickets->count();
        $sousPoidsAgent = $groupTickets->sum(fn ($t) => (float) ($t->poids ?? 0));
      @endphp
      <div class="pdf-agent-bloc">
        <div class="pdf-agent-titre">{{ $nomAgentTitre }}</div>
        <table>
          <thead>
            <tr>
              <th>N° ticket</th>
              <th>Date ticket</th>
              <th>Date réception</th>
              <th class="num">Poids (kg)</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($groupTickets as $ticket)
              <tr>
                <td>{{ $ticket->numero_ticket }}</td>
                <td>{{ $ticket->date_ticket?->format('d/m/Y') ?? '—' }}</td>
                <td>{{ $ticket->created_at?->format('d/m/Y') ?? '—' }}</td>
                <td class="num">
                  @if ($ticket->poids !== null)
                    {{ number_format((float) $ticket->poids, 0, ',', ' ') }}
                  @else
                    —
                  @endif
                </td>
              </tr>
            @endforeach
            <tr class="sous-total-row">
              <td colspan="3" class="sous-total-label">Sous-total {{ $nomAgent }} ({{ $nbAgent }} {{ $nbAgent > 1 ? 'tickets' : 'ticket' }}) — poids (kg)</td>
              <td class="num">{{ number_format(round($sousPoidsAgent), 0, ',', ' ') }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    @endforeach

    <table class="total-general">
      <tbody>
        <tr class="total-row">
          <td colspan="3" class="sous-total-label">Total général ({{ $tickets->count() }} {{ $tickets->count() > 1 ? 'tickets' : 'ticket' }}) — poids usine (kg)</td>
          <td class="num">{{ number_format(round($totalPoidsKg), 0, ',', ' ') }}</td>
        </tr>
      </tbody>
    </table>
  @endif

  <p class="pdf-verificateur"><strong>Vérificateur :</strong> {{ $verifierName }}</p>
  </div>
</body>
</html>
