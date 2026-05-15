@php
    $formAction = $formAction ?? route('verifications');
    $linkRows = $linkRows ?? true;
    $searchInputId = $searchInputId ?? 'search-usine';
    $pageTitle = $pageTitle ?? 'Liste des usines';
    $pageSubtitle = $pageSubtitle ?? 'Consultez et accédez aux sites de vérification des tickets.';
    $total = (int) ($pagination['total'] ?? count($usines));
    $current = (int) ($pagination['current_page'] ?? 1);
    $last = (int) ($pagination['last_page'] ?? 1);
    $ticketCountsByUsine = $ticketCountsByUsine ?? [];
    $totalTicketsPage = array_sum(array_map(
        static fn (array $u) => (int) ($ticketCountsByUsine[(int) ($u['id_usine'] ?? 0)] ?? 0),
        $usines
    ));
@endphp

<div class="app-list-page">
  <div class="app-page-header d-flex flex-column flex-md-row align-items-md-start justify-content-md-between gap-3 mb-4">
    <div class="d-flex align-items-start gap-3">
      <div class="app-page-header-icon flex-shrink-0">
        <i class="bx bx-buildings"></i>
      </div>
      <div>
        <h4 class="mb-1 app-page-title">{{ $pageTitle }}</h4>
        <p class="mb-0 text-muted app-page-subtitle">{{ $pageSubtitle }}</p>
      </div>
    </div>
    @if (! $error && $total > 0)
      <div class="d-flex flex-wrap gap-2 align-self-md-center">
        <span class="badge rounded-pill bg-label-primary px-3 py-2">
          {{ number_format($total, 0, ',', ' ') }} usine{{ $total > 1 ? 's' : '' }}
        </span>
        <span class="badge rounded-pill bg-label-success px-3 py-2">
          {{ number_format($totalTicketsPage, 0, ',', ' ') }} ticket{{ $totalTicketsPage > 1 ? 's' : '' }} validé{{ $totalTicketsPage > 1 ? 's' : '' }} (page)
        </span>
      </div>
    @endif
  </div>

  @if ($error)
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert">
      <i class="bx bx-error-circle fs-4"></i>
      <span>{{ $error }}</span>
    </div>
  @endif

  <div class="card app-list-card border-0 shadow-sm">
    <div class="card-header app-list-card-header border-bottom bg-transparent px-4 py-3">
      <form method="get" action="{{ $formAction }}" class="app-filter-form">
        <div class="row g-2 g-md-3 align-items-center">
          <div class="col-12 col-lg">
            <label class="form-label visually-hidden" for="{{ $searchInputId }}">Rechercher une usine</label>
            <div class="input-group input-group-merge">
              <span class="input-group-text border-end-0"><i class="bx bx-search text-muted"></i></span>
              <input
                type="search"
                class="form-control border-start-0 ps-0"
                id="{{ $searchInputId }}"
                name="search"
                value="{{ $search }}"
                placeholder="Rechercher par nom d'usine…"
                autocomplete="off"
              />
            </div>
          </div>
          <div class="col-12 col-lg-auto d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="bx bx-filter-alt me-1"></i>Rechercher
            </button>
            <a href="{{ $formAction }}" class="btn btn-outline-secondary">Réinitialiser</a>
          </div>
        </div>
      </form>
    </div>

    @if (! $error && count($usines) > 0)
      <div class="app-usine-table-toolbar px-4 py-2 border-bottom">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
          <span class="text-muted small">
            <i class="bx bx-list-ul me-1"></i>{{ count($usines) }} usine{{ count($usines) > 1 ? 's' : '' }} sur cette page
          </span>
          <span class="text-muted small">
            <i class="bx bx-check-circle me-1 text-success"></i>{{ number_format($totalTicketsPage, 0, ',', ' ') }} ticket{{ $totalTicketsPage > 1 ? 's' : '' }} validé{{ $totalTicketsPage > 1 ? 's' : '' }}
          </span>
        </div>
      </div>
    @endif

    <div class="table-responsive app-usine-table-wrap">
      <table class="table app-data-table app-usine-table mb-0">
        <thead>
          <tr>
            <th class="app-usine-th app-usine-th-name">Nom de l'usine</th>
            <th class="app-usine-th app-usine-th-tickets text-center">Tickets validés</th>
            @if ($linkRows)
              <th class="app-usine-th app-usine-th-action text-end">Accès</th>
            @endif
          </tr>
        </thead>
        <tbody>
          @forelse ($usines as $u)
            @php
              $nom = $u['nom_usine'] ?? '—';
              $id = $u['id_usine'] ?? '';
              $idUsine = (int) $id;
              $nbTickets = (int) ($ticketCountsByUsine[$idUsine] ?? 0);
              $usineUrl = ($linkRows && $id !== '') ? route('verifications.usine', ['id_usine' => $id]) : null;
            @endphp
            <tr class="app-usine-table-row @if($usineUrl) app-usine-table-row--linked @endif">
              <td class="app-usine-td-name" data-label="Usine">
                @if ($usineUrl)
                  <a href="{{ $usineUrl }}" class="app-table-row-link">
                    <span class="app-table-row-icon"><i class="bx bx-buildings"></i></span>
                    <span class="app-table-row-label">{{ $nom }}</span>
                  </a>
                @else
                  <span class="d-inline-flex align-items-center gap-2">
                    <span class="app-table-row-icon"><i class="bx bx-buildings"></i></span>
                    <span class="fw-medium text-heading">{{ $nom }}</span>
                  </span>
                @endif
              </td>
              <td class="app-usine-td-tickets text-center" data-label="Tickets validés">
                <span class="app-usine-ticket-count @if($nbTickets > 0) app-usine-ticket-count--has @else app-usine-ticket-count--zero @endif" title="Tickets validés enregistrés pour cette usine">
                  <i class="bx @if($nbTickets > 0) bx-check-circle @else bx-minus-circle @endif"></i>
                  <span class="app-usine-ticket-count-value">{{ number_format($nbTickets, 0, ',', ' ') }}</span>
                </span>
              </td>
              @if ($linkRows)
                <td class="app-usine-td-action text-end" data-label="Accès">
                  @if ($usineUrl)
                    <a href="{{ $usineUrl }}" class="btn btn-sm btn-outline-primary app-usine-open-btn" title="Ouvrir l'usine">
                      Ouvrir <i class="bx bx-chevron-right ms-1"></i>
                    </a>
                  @endif
                </td>
              @endif
            </tr>
          @empty
            <tr>
              <td colspan="{{ $linkRows ? 3 : 2 }}" class="p-0 border-0">
                <div class="app-table-empty py-5 px-4 text-center">
                  <div class="app-table-empty-icon mx-auto mb-3">
                    <i class="bx bx-buildings"></i>
                  </div>
                  <p class="mb-1 fw-medium text-heading">Aucune usine trouvée</p>
                  <p class="mb-0 text-muted small">
                    @if ($error)
                      Les données n'ont pas pu être chargées.
                    @elseif ($search !== '')
                      Aucun résultat pour « {{ $search }} ». Modifiez votre recherche ou réinitialisez les filtres.
                    @else
                      Aucune usine n'est disponible pour le moment.
                    @endif
                  </p>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($pagination && count($usines) > 0)
      <div class="card-footer app-list-card-footer border-top bg-transparent px-4 py-3">
        <div class="d-flex flex-column flex-sm-row align-items-center justify-content-between gap-3">
          <small class="text-muted">
            {{ number_format($total, 0, ',', ' ') }} usine{{ $total > 1 ? 's' : '' }} au total
            @if ($last > 1)
              — page {{ $current }} / {{ max(1, $last) }}
            @endif
          </small>
          @if ($last > 1)
            <nav class="d-flex gap-2" aria-label="Pagination">
              @if ($current > 1)
                <a class="btn btn-sm btn-outline-primary" href="{{ $formAction }}?{{ http_build_query(array_merge(request()->except('page'), ['page' => $current - 1])) }}">
                  <i class="bx bx-chevron-left"></i> Précédent
                </a>
              @endif
              @if ($current < $last)
                <a class="btn btn-sm btn-outline-primary" href="{{ $formAction }}?{{ http_build_query(array_merge(request()->except('page'), ['page' => $current + 1])) }}">
                  Suivant <i class="bx bx-chevron-right"></i>
                </a>
              @endif
            </nav>
          @endif
        </div>
      </div>
    @endif
  </div>
</div>
