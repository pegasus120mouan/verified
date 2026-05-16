<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <title>Tickets introuvables</title>
  <style>
    body {
      font-family: DejaVu Sans, sans-serif;
      font-size: 10px;
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
    h1 { font-size: 14px; margin: 0 0 8px; color: #e74c3c; }
    .meta { font-size: 9px; color: #444; margin-bottom: 14px; }
    .meta p { margin: 2px 0; }
    .warning-box {
      background: #fff3cd;
      border: 1px solid #ffc107;
      border-radius: 4px;
      padding: 8px 12px;
      margin-bottom: 14px;
      font-size: 9px;
    }
    table { width: 100%; border-collapse: collapse; font-size: 9px; }
    th, td { border: 1px solid #999; padding: 5px 6px; text-align: left; vertical-align: top; }
    th { background: #f8d7da; font-weight: bold; color: #721c24; }
    .total-row td { font-weight: bold; background: #f0f0f0; }
    .pdf-footer {
      margin-top: 20px;
      padding-top: 10px;
      border-top: 1px solid #bbb;
      font-size: 8.5px;
    }
    .numero-cell { font-family: monospace; font-weight: bold; }
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
    <h1>Tickets introuvables</h1>
    <div class="meta">
      <p><strong>Usine :</strong> {{ $nomUsine }} (réf. #{{ $idUsine }})</p>
      <p><strong>Généré le :</strong> {{ $generatedAt }}</p>
    </div>

    <div class="warning-box">
      <strong>Attention :</strong> Les numéros de tickets ci-dessous n'ont pas été trouvés dans la base de donnée générale pour cette usine.
      Ils peuvent être rattachés à une autre usine ou ne pas exister dans le système.
    </div>

    @if (count($numeros) === 0)
      <p style="text-align:center;color:#666;">Aucun ticket introuvable.</p>
    @else
      <table>
        <thead>
          <tr>
            <th style="width: 50px;">#</th>
            <th>Numéro de ticket</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($numeros as $index => $numero)
            <tr>
              <td>{{ $index + 1 }}</td>
              <td class="numero-cell">{{ $numero }}</td>
            </tr>
          @endforeach
          <tr class="total-row">
            <td colspan="2">Total : {{ count($numeros) }} ticket(s) introuvable(s)</td>
          </tr>
        </tbody>
      </table>
    @endif

    <div class="pdf-footer">
      <p><strong>Généré par :</strong> {{ $userName }}</p>
    </div>
  </div>
</body>
</html>
