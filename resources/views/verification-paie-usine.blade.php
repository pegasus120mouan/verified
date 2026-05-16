@extends('layout.main')

@section('title')
  Vérification paie — {{ $usine['nom_usine'] ?? 'Usine' }}
@endsection

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="mb-3">
      <a href="{{ route('verification-paie.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bx bx-chevron-left me-1"></i>Retour à la liste des usines
      </a>
    </div>

    @if ($error)
      <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
        <i class="bx bx-error-circle fs-4"></i>
        <span>{{ $error }}</span>
      </div>
    @endif

    @if (session('flash_error'))
      <div class="alert alert-danger alert-dismissible" role="alert">
        {{ session('flash_error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
      </div>
    @endif

    @if ($usine && ! $error)
      <div class="card border-0 shadow-sm mb-4 position-relative" id="paie-excel-card">
          <div
            id="paie-excel-loading-overlay"
            class="position-absolute top-0 start-0 w-100 h-100 d-none align-items-center justify-content-center rounded"
            style="z-index: 10; background: rgba(255, 255, 255, 0.92); backdrop-filter: blur(2px);"
            aria-hidden="true"
            role="status"
          >
            <div class="text-center px-4 py-5">
              <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" aria-hidden="true"></div>
              <p class="fw-medium mb-1">Vérification en cours…</p>
              <p class="text-muted small mb-0">Lecture du fichier et interrogation de l’API Pegasus (peut prendre une minute).</p>
            </div>
          </div>
        <div class="card-body">
          <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div class="d-flex align-items-start gap-3">
              <div class="app-page-header-icon flex-shrink-0" style="background:rgba(40,199,111,.12);color:#28c76f;">
                <i class="bx bx-wallet"></i>
              </div>
              <div>
                <h4 class="mb-1 app-page-title">Vérification paie</h4>
                <p class="mb-0 text-muted">
                  <strong class="text-heading text-uppercase">{{ $usine['nom_usine'] ?? '—' }}</strong>
                  <span class="text-muted small ms-1">Réf. #{{ $id_usine }}</span>
                </p>
              </div>
            </div>
            <a href="{{ route('verification-paie.template') }}" class="btn btn-outline-primary btn-sm">
              <i class="bx bx-download me-1"></i>Télécharger le modèle Excel
            </a>
          </div>

          <hr class="my-4" />

          <p class="text-muted small mb-3">
            Importez un fichier Excel ou CSV contenant une colonne <strong>NUMERO_TICKET</strong> (recommandé),
            ou des numéros en <strong>colonne A</strong>. Chaque ligne est comparée à l’API Pegasus (tickets mes_tickets) pour cette usine.
            Les numéros absents sont enregistrés dans <strong>tickets introuvables</strong> (base locale).
            Si les fichiers <strong>.xlsx</strong> échouent, activez <code>extension=zip</code> dans php.ini ou utilisez un export <strong>.csv</strong>.
          </p>

          <form
            id="form-paie-excel-verify"
            method="post"
            action="{{ route('verification-paie.excel', ['id_usine' => $id_usine]) }}"
            enctype="multipart/form-data"
            class="row g-3 align-items-end"
          >
            @csrf
            <div class="col-12 col-md-8">
              <label class="form-label" for="excel-paie-file">Fichier (.xlsx, .xls, .csv)</label>
              <input
                type="file"
                name="excel_file"
                id="excel-paie-file"
                class="form-control @error('excel_file') is-invalid @enderror"
                accept=".xlsx,.xls,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv"
                required
              />
              @error('excel_file')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            <div class="col-12 col-md-4 d-flex flex-wrap gap-2">
              <button type="submit" class="btn btn-primary" id="btn-paie-excel-submit">
                <span class="btn-paie-excel-label"><i class="bx bx-upload me-1"></i>Vérifier les tickets</span>
                <span class="btn-paie-excel-spinner d-none spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
              </button>
            </div>
          </form>
        </div>
      </div>

      @if (! empty($summary))
        <div class="row g-3 mb-4">
          <div class="col-6 col-md-3">
            <div class="border rounded p-3 text-center bg-label-secondary bg-opacity-10">
              <div class="fw-bold fs-4">{{ $summary['total'] ?? 0 }}</div>
              <small class="text-muted">Numéros lus</small>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div 
              class="border rounded p-3 text-center bg-label-success bg-opacity-10" 
              style="cursor: pointer;" 
              id="filter-trouve-box"
              title="Cliquez pour imprimer les tickets trouvés"
            >
              <div class="fw-bold fs-4 text-success">{{ $summary['trouve_api'] ?? 0 }}</div>
              <small class="text-muted">Dans la base générale</small>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="border rounded p-3 text-center bg-label-info bg-opacity-10">
              <div class="fw-bold fs-4 text-info">{{ $summary['deja_local'] ?? 0 }}</div>
              <small class="text-muted">Déjà en base locale</small>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div 
              class="border rounded p-3 text-center bg-label-warning bg-opacity-10" 
              style="cursor: pointer;" 
              id="filter-introuvable-box"
              title="Cliquez pour afficher uniquement les tickets introuvables"
            >
              <div class="fw-bold fs-4 text-warning">{{ ($summary['introuvable'] ?? 0) + ($summary['mauvaise_usine'] ?? 0) }}</div>
              <small class="text-muted">Introuvable / autre usine</small>
            </div>
          </div>
        </div>
      @endif

      @if (! empty($results))
        
        <div class="card border-0 shadow-sm">
          <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Résultats</h5>
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="btn-show-all">
                <i class="bx bx-list-ul me-1"></i>Afficher tout
              </button>
              <button type="button" class="btn btn-sm btn-outline-danger d-none" id="btn-print-introuvables">
                <i class="bx bx-printer me-1"></i>Imprimer introuvables
              </button>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead>
                <tr>
                  <th>N° ticket</th>
                  <th>Date ticket</th>
                  <th>Poids</th>
                  <th>Date création</th>
                  <th>Statut</th>
                  <th>Motif</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($results as $row)
                  @php
                    $statut = $row['statut'] ?? '';
                    $isFound = in_array($statut, ['trouve_api', 'deja_local']);
                    $isIntrouvable = in_array($statut, ['introuvable', 'mauvaise_usine']);
                    $icon = $isFound ? 'yes.png' : 'no.png';
                    $dateTicket = isset($row['date_ticket']) ? \Carbon\Carbon::parse($row['date_ticket'])->format('d/m/Y') : '—';
                    $poids = isset($row['poids']) ? number_format((float) $row['poids'], 0, ',', ' ') . ' kg' : '—';
                    $createdAt = isset($row['created_at']) ? \Carbon\Carbon::parse($row['created_at'])->format('d/m/Y') : '—';
                    $motif = match ($statut) {
                        'introuvable', 'mauvaise_usine' => 'Introuvable dans la base de donnée Generale',
                        'trouve_api' => 'Présent dans la base de donnée Generale',
                        'deja_local' => 'Déjà enregistré en base locale',
                        default => '—',
                    };
                  @endphp
                  <tr data-statut="{{ $statut }}" class="ticket-row">
                    <td class="fw-semibold">{{ $row['numero'] ?? '—' }}</td>
                    <td>{{ $dateTicket }}</td>
                    <td>{{ $poids }}</td>
                    <td>{{ $createdAt }}</td>
                    <td class="text-center"><img src="{{ asset('img/icons/unicons/' . $icon) }}" alt="{{ $isFound ? 'Trouvé' : 'Introuvable' }}" style="width: 24px; height: 24px;"></td>
                    <td>
                      @if ($isIntrouvable)
                        <span class="badge bg-label-warning">{{ $motif }}</span>
                      @elseif ($isFound)
                        <span class="badge bg-label-success">{{ $motif }}</span>
                      @else
                        <span class="text-muted small">{{ $motif }}</span>
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      @endif
    @endif
  </div>
  <div class="content-backdrop fade"></div>
@endsection

@push('page-scripts')
  <script>
    (function () {
      const form = document.getElementById('form-paie-excel-verify');
      if (!form) return;

      const btn = document.getElementById('btn-paie-excel-submit');
      const overlay = document.getElementById('paie-excel-loading-overlay');
      const labelEl = form.querySelector('.btn-paie-excel-label');
      const spinnerSmall = form.querySelector('.btn-paie-excel-spinner');

      form.addEventListener('submit', function () {
        if (!form.checkValidity()) return;
        if (btn) {
          btn.disabled = true;
        }
        // Ne pas désactiver l'input fichier : un champ <input disabled> n'est pas envoyé avec le POST.
        if (overlay) {
          overlay.classList.remove('d-none');
          overlay.classList.add('d-flex');
          overlay.setAttribute('aria-hidden', 'false');
        }
        if (labelEl) {
          labelEl.innerHTML =
            '<i class="bx bx-loader-alt bx-spin me-1"></i>Vérification en cours…';
        }
        if (spinnerSmall) {
          spinnerSmall.classList.add('d-none');
        }
      });

      // Filtrage des tickets introuvables
      const filterBox = document.getElementById('filter-introuvable-box');
      const btnShowAll = document.getElementById('btn-show-all');
      const btnPrint = document.getElementById('btn-print-introuvables');
      const rows = document.querySelectorAll('.ticket-row');

      if (filterBox && rows.length > 0) {
        let isFiltered = false;

        filterBox.addEventListener('click', function () {
          if (isFiltered) return;
          isFiltered = true;
          
          rows.forEach(row => {
            const statut = row.getAttribute('data-statut');
            if (statut !== 'introuvable' && statut !== 'mauvaise_usine') {
              row.style.display = 'none';
            }
          });

          filterBox.style.outline = '3px solid #ff9f43';
          filterBox.style.outlineOffset = '2px';
          
          if (btnShowAll) btnShowAll.classList.remove('d-none');
          if (btnPrint) btnPrint.classList.remove('d-none');
        });

        if (btnShowAll) {
          btnShowAll.addEventListener('click', function () {
            isFiltered = false;
            rows.forEach(row => {
              row.style.display = '';
            });
            filterBox.style.outline = '';
            filterBox.style.outlineOffset = '';
            btnShowAll.classList.add('d-none');
            btnPrint.classList.add('d-none');
          });
        }

        if (btnPrint) {
          btnPrint.addEventListener('click', function () {
            // Utiliser les données JSON complètes
            const ticketsData = JSON.parse(document.getElementById('tickets-data')?.textContent || '{}');
            const numeros = ticketsData.introuvables || [];

            if (numeros.length === 0) {
              alert('Aucun ticket introuvable à imprimer.');
              return;
            }

            // Créer un formulaire pour envoyer les données
            const printForm = document.createElement('form');
            printForm.method = 'POST';
            printForm.action = '{{ route("verification-paie.print-introuvables", ["id_usine" => $id_usine]) }}';
            printForm.target = '_blank';

            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = '{{ csrf_token() }}';
            printForm.appendChild(csrfInput);

            numeros.forEach(numero => {
              const input = document.createElement('input');
              input.type = 'hidden';
              input.name = 'numeros[]';
              input.value = numero;
              printForm.appendChild(input);
            });

            document.body.appendChild(printForm);
            printForm.submit();
            document.body.removeChild(printForm);
          });
        }
      }

      // Impression des tickets trouvés dans la base générale
      const filterTrouveBox = document.getElementById('filter-trouve-box');
      if (filterTrouveBox) {
        filterTrouveBox.addEventListener('click', function () {
          // Ouvrir directement le PDF - les données sont récupérées depuis le cache côté serveur
          window.open('{{ route("verification-paie.print-trouves", ["id_usine" => $id_usine]) }}', '_blank');
        });
      }
    })();
  </script>
@endpush
