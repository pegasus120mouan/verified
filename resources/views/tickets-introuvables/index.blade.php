@extends('layout.main')

@section('title')
  Tickets introuvables
@endsection

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <div class="rounded-3 p-4 h-100 shadow-sm" style="background:#ff9f43;color:#fff;">
          <div style="font-size:2.25rem;font-weight:700;line-height:1.1;color:#fff;">{{ number_format($stats['total'] ?? 0, 0, ',', ' ') }}</div>
          <p class="mb-0 mt-2" style="font-size:0.9375rem;color:#fff;">Tickets introuvables — <strong>Total</strong></p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="rounded-3 p-4 h-100 shadow-sm" style="background:#00cfe8;color:#fff;">
          <div style="font-size:2.25rem;font-weight:700;line-height:1.1;color:#fff;">{{ number_format($stats['today'] ?? 0, 0, ',', ' ') }}</div>
          <p class="mb-0 mt-2" style="font-size:0.9375rem;color:#fff;">Signalés <strong>aujourd'hui</strong></p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="rounded-3 p-4 h-100 shadow-sm" style="background:#7367f0;color:#fff;">
          <div style="font-size:2.25rem;font-weight:700;line-height:1.1;color:#fff;">{{ number_format($stats['usines'] ?? 0, 0, ',', ' ') }}</div>
          <p class="mb-0 mt-2" style="font-size:0.9375rem;color:#fff;">Usines <strong>concernées</strong></p>
        </div>
      </div>
    </div>
    <div class="app-page-header d-flex flex-column flex-md-row align-items-md-start justify-content-md-between gap-3 mb-4">
      <div class="d-flex align-items-start gap-3">
        <div class="app-page-header-icon flex-shrink-0" style="background:rgba(255,159,67,.12);color:#ff9f43;">
          <i class="bx bx-error-circle"></i>
        </div>
        <div>
          <h4 class="mb-1 app-page-title">Tickets introuvables</h4>
          <p class="mb-0 text-muted app-page-subtitle">
            Numéros absents de l'API Pegasus — enregistrés en base locale, visibles par tous les vérificateurs de cette application.
          </p>
        </div>
      </div>
      <span class="badge rounded-pill bg-label-warning px-3 py-2 align-self-md-center">
        {{ number_format($total, 0, ',', ' ') }} enregistrement{{ $total > 1 ? 's' : '' }}
      </span>
    </div>

    <div class="card app-list-card border-0 shadow-sm">
      <div class="card-header app-list-card-header border-bottom bg-transparent px-4 py-3">
        <form method="get" action="{{ route('tickets-introuvables.index') }}" class="row g-2 g-md-3 align-items-end">
          <div class="col-12 col-md-5">
            <label class="form-label" for="search-introuvable">Numéro de ticket</label>
            <div class="input-group input-group-merge">
              <span class="input-group-text border-end-0"><i class="bx bx-search text-muted"></i></span>
              <input
                type="search"
                class="form-control border-start-0 ps-0"
                id="search-introuvable"
                name="search"
                value="{{ $search }}"
                placeholder="Rechercher un numéro…"
                autocomplete="off"
              />
            </div>
          </div>
          <div class="col-12 col-md-3">
            <label class="form-label" for="filter-usine-introuvable">Usine (ID)</label>
            <input
              type="text"
              class="form-control"
              id="filter-usine-introuvable"
              name="usine"
              value="{{ $usineFilter }}"
              placeholder="ex. 27"
              inputmode="numeric"
            />
          </div>
          <div class="col-12 col-md-4 d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="bx bx-filter-alt me-1"></i>Filtrer
            </button>
            <a href="{{ route('tickets-introuvables.index') }}" class="btn btn-outline-secondary">Réinitialiser</a>
          </div>
        </form>
      </div>

      <div class="table-responsive app-usine-table-wrap">
        <table class="table app-data-table app-usine-table mb-0">
          <thead>
            <tr>
              <th class="app-usine-th app-usine-th-name">N° ticket</th>
              <th class="app-usine-th">Usine</th>
              <th class="app-usine-th">Motif</th>
              <th class="app-usine-th">Signalé par</th>
              <th class="app-usine-th">Enregistré le</th>
              <th class="app-usine-th app-usine-th-tickets text-center">Mise à jour</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($tickets as $ticket)
              @php
                $nomUsine = $ticket->id_usine ? ($usineNames[$ticket->id_usine] ?? null) : null;
                $raisonLabel = match ($ticket->raison) {
                    'not_found' => 'Ticket introuvable dans la base Générale',
                    default => $ticket->raison,
                };
              @endphp
              <tr>
                <td class="app-usine-td-name">
                  <span class="fw-semibold text-heading">{{ $ticket->numero_ticket }}</span>
                </td>
                <td>
                  @if ($ticket->id_usine && $nomUsine)
                    <span class="d-inline-flex align-items-center gap-2">
                      <span class="app-table-row-icon"><i class="bx bx-buildings"></i></span>
                      <span>{{ $nomUsine }}</span>
                    </span>
                    <span class="text-muted small ms-1">#{{ $ticket->id_usine }}</span>
                  @elseif ($ticket->id_usine)
                    <span class="text-muted">Usine #{{ $ticket->id_usine }}</span>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td>
                  <span class="badge bg-label-warning">{{ $raisonLabel }}</span>
                </td>
                <td>
                  {{ $ticket->utilisateur?->name ?: ($ticket->utilisateur?->login ?? '—') }}
                </td>
                <td class="text-nowrap">
                  {{ $ticket->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—' }}
                </td>
                <td class="text-center text-nowrap">
                  @if ($ticket->updated_at && $ticket->updated_at->ne($ticket->created_at))
                    <span class="text-muted small">{{ $ticket->updated_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</span>
                  @endif
                  <form action="{{ route('tickets-introuvables.reverify', $ticket->id) }}" method="POST" class="d-inline reverify-form">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-primary btn-reverify" title="Re-vérifier dans l'API">
                      <i class="bx bx-refresh btn-icon"></i>
                      <i class="bx bx-loader-alt bx-spin btn-loader d-none"></i>
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="p-0 border-0">
                  <div class="app-table-empty py-5 px-4 text-center">
                    <div class="app-table-empty-icon mx-auto mb-3">
                      <i class="bx bx-error-circle"></i>
                    </div>
                    <p class="mb-1 fw-medium text-heading">Aucun ticket introuvable</p>
                    <p class="mb-0 text-muted small">
                      @if ($search !== '' || $usineFilter !== '')
                        Aucun résultat pour ces filtres.
                      @else
                        Les tickets non trouvés lors d'une vérification apparaîtront ici.
                      @endif
                    </p>
                  </div>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if ($tickets->hasPages())
        <div class="card-footer app-list-card-footer border-top bg-transparent px-4 py-3">
          <div class="d-flex flex-column flex-sm-row align-items-center justify-content-between gap-3">
            <small class="text-muted">
              {{ $tickets->firstItem() }}–{{ $tickets->lastItem() }} sur {{ number_format($tickets->total(), 0, ',', ' ') }}
            </small>
            {{ $tickets->links('pagination::bootstrap-5') }}
          </div>
        </div>
      @endif
    </div>
  </div>
@endsection

@push('scripts')
  <script>
    document.querySelectorAll('.reverify-form').forEach(form => {
      form.addEventListener('submit', function() {
        const btn = this.querySelector('.btn-reverify');
        const icon = btn.querySelector('.btn-icon');
        const loader = btn.querySelector('.btn-loader');
        
        btn.disabled = true;
        icon.classList.add('d-none');
        loader.classList.remove('d-none');
      });
    });
  </script>
@endpush
