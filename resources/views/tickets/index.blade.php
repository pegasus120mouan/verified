@extends('layout.main')

@section('title')
 Liste des tickets vérifiés
@endsection

@section('content')
            <!-- Content -->
            <div class="container-xxl flex-grow-1 container-p-y">
              @if ($errors->any())
                <div class="alert alert-danger alert-dismissible mb-4" role="alert">
                  <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $message)
                      <li>{{ $message }}</li>
                    @endforeach
                  </ul>
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
              @endif

              <h4 class="mb-4">Mes tickets</h4>

              <div class="card mb-4">
                <div class="card-body">
                  <form method="get" action="{{ route('tickets.index') }}" class="row g-3 align-items-end">
                    <div class="col-md-6">
                      <label class="form-label" for="filter-usine">Usine (ID)</label>
                      <input type="text" class="form-control" id="filter-usine" name="usine" value="{{ request('usine') }}" placeholder="ID usine (ex. 11)" inputmode="numeric" />
                    </div>
                    <div class="col-md-6">
                      <label class="form-label" for="filter-agent">Agent (ID)</label>
                      <input type="text" class="form-control" id="filter-agent" name="agent" value="{{ request('agent') }}" placeholder="ID agent (ex. 36)" inputmode="numeric" />
                    </div>
                    <div class="col-12 d-flex flex-wrap gap-2">
                      <button type="submit" class="btn btn-primary">Rechercher</button>
                      <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary">Réinitialiser</a>
                    </div>
                  </form>
                </div>
              </div>

              <div class="d-flex justify-content-end mb-3">
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#ticketPrintModal">
                  <i class="icon-base bx bx-printer me-1"></i>Imprimer
                </button>
              </div>

              <div class="card">
                <div class="table-responsive text-nowrap">
                  <table class="table table-hover mb-0">
                    <thead>
                      <tr>
                        <th>DATE TICKET</th>
                        <th>N°TICKET</th>
                        <th>USINE</th>
                        <th>AGENT</th>
                        <th>DATE RÉCEPTION</th>
                        <th>POIDS USINE</th>
                        <th>PRIX U</th>
                        <th>ACTIONS</th>
                      </tr>
                    </thead>
                    <tbody>
                      @forelse ($tickets as $ticket)
                        <tr>
                          <td>{{ $ticket->date_ticket?->format('d/m/Y') ?? '—' }}</td>
                          <td><span class="fw-medium">{{ $ticket->numero_ticket }}</span></td>
                          <td>
                            @php
                              $nomUsine = $usineNames[$ticket->id_usine] ?? null;
                            @endphp
                            @if($nomUsine)
                              <span class="fw-medium text-heading text-uppercase">{{ $nomUsine }}</span>
                            @else
                              <span class="text-muted">—</span>
                            @endif
                          </td>
                          <td>
                            @php
                              $nomAgent = $agentNames[$ticket->id_agent] ?? null;
                            @endphp
                            @if($nomAgent)
                              <span class="fw-medium text-heading">{{ $nomAgent }}</span>
                            @else
                              <span class="text-muted">—</span>
                            @endif
                          </td>
                          <td>{{ $ticket->created_at?->format('d/m/Y') ?? '—' }}</td>
                          <td>
                            @if($ticket->poids !== null)
                              {{ number_format((float) $ticket->poids, 0, ',', ' ') }} kg
                            @else
                              —
                            @endif
                          </td>
                          <td>{{ number_format((float) $ticket->prix_unitaire, 2, ',', ' ') }}</td>
                          <td>
                            <a href="{{ route('tickets.show', $ticket) }}" class="btn btn-sm btn-outline-primary">Voir</a>
                          </td>
                        </tr>
                      @empty
                        <tr>
                          <td colspan="8" class="text-center text-muted py-5">Aucun ticket</td>
                        </tr>
                      @endforelse
                    </tbody>
                  </table>
                </div>
                @if($tickets->hasPages())
                  <div class="card-footer pb-0">
                    {{ $tickets->links('pagination::bootstrap-5') }}
                  </div>
                @endif
              </div>
            </div>
            <!-- / Content -->

            <div class="content-backdrop fade"></div>

            <div class="modal fade" id="ticketPrintModal" tabindex="-1" aria-labelledby="ticketPrintModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="ticketPrintModalLabel">Imprimer les tickets</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                  </div>
                  <form id="ticketPrintForm" method="get" action="{{ route('tickets.print') }}" target="_blank">
                    <div class="modal-body">
                      <p class="text-muted small mb-3">Les dates filtrent sur la <strong>date de réception</strong> (enregistrement local), sans l’heure.</p>
                      <div class="mb-3">
                        <label class="form-label" for="print-id-agent">Agent</label>
                        <select class="form-select" id="print-id-agent" name="id_agent">
                          <option value="">Tous les agents</option>
                          @foreach ($agentsPrintOptions as $opt)
                            <option value="{{ $opt['id'] }}" @selected((string) old('id_agent') === (string) $opt['id'])>{{ $opt['nom'] }}</option>
                          @endforeach
                        </select>
                      </div>
                      <div class="row g-3">
                        <div class="col-md-6">
                          <label class="form-label" for="print-date-debut">Date début</label>
                          <input type="date" class="form-control" id="print-date-debut" name="date_debut" value="{{ old('date_debut') }}" />
                        </div>
                        <div class="col-md-6">
                          <label class="form-label" for="print-date-fin">Date fin</label>
                          <input type="date" class="form-control" id="print-date-fin" name="date_fin" value="{{ old('date_fin') }}" />
                        </div>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                      <button type="submit" class="btn btn-primary">Afficher et imprimer</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>

@endsection

@push('page-scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      @if (session('open_print_modal'))
        var el = document.getElementById('ticketPrintModal');
        if (el && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
          bootstrap.Modal.getOrCreateInstance(el).show();
        }
      @endif

      var printForm = document.getElementById('ticketPrintForm');
      if (printForm) {
        printForm.addEventListener('submit', function (e) {
          var d1 = document.getElementById('print-date-debut');
          var d2 = document.getElementById('print-date-fin');
          if (d1 && d2 && d1.value && d2.value && d2.value < d1.value) {
            e.preventDefault();
            alert('La date fin doit être après ou égale à la date début.');
          }
        });
      }
    });
  </script>
@endpush
