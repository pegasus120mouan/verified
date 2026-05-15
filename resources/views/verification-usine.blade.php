@extends('layout.main')

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

              @if (session('error'))
                <div class="alert alert-danger alert-dismissible mb-4" role="alert">
                  {{ session('error') }}
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
              @endif

              <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <a href="{{ route('verifications') }}" class="btn btn-sm btn-outline-primary">
                  <i class="icon-base bx bx-chevron-left"></i> Retour à la liste
                </a>
                @if (($usine ?? null) && ! $error)
                  <button type="button" class="btn btn-sm btn-primary js-open-ticket-verify-modal">
                    <i class="icon-base bx bx-check-circle me-1"></i>Vérifier un ticket
                  </button>
                @endif
              </div>

              @if ($error)
                <div class="alert alert-danger" role="alert">{{ $error }}</div>
              @elseif ($usine ?? null)
                <div class="card mb-4">
                  <div class="card-header d-flex flex-wrap align-items-start justify-content-between gap-2">
                    <div>
                      <h5 class="mb-0">{{ $usine['nom_usine'] ?? 'Usine' }}</h5>
                      <small class="text-muted">Réf. #{{ $usine['id_usine'] ?? '' }}</small>
                    </div>
                  </div>
                </div>

                @if ($tickets !== null)
                  <div class="d-flex justify-content-end mb-3">
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#pointTonnageModal">
                      <i class="icon-base bx bx-printer me-1"></i>Imprimer point tonnage
                    </button>
                  </div>
                  <div class="card">
                    <div class="card-header">
                      <h5 class="mb-0">Tickets vérifiés (enregistrés localement)</h5>
                      <small class="text-muted">Vos enregistrements pour cette usine</small>
                    </div>
                    <div class="table-responsive text-nowrap">
                      <table class="table table-hover mb-0">
                        <thead>
                          <tr>
                            <th>Date ticket</th>
                            <th>N° ticket</th>
                            <th>Agent</th>
                            <th>Date réception</th>
                            <th>Poids usine</th>
                            <th>Prix U</th>
                            <th></th>
                          </tr>
                        </thead>
                        <tbody>
                          @forelse ($tickets as $ticket)
                            <tr>
                              <td>{{ $ticket->date_ticket?->format('d/m/Y') ?? '—' }}</td>
                              <td><span class="fw-medium">{{ $ticket->numero_ticket }}</span></td>
                              <td>
                                @php
                                  $nomAgent = $agentNames[$ticket->id_agent] ?? null;
                                @endphp
                                @if ($nomAgent)
                                  <span class="fw-medium text-heading">{{ $nomAgent }}</span>
                                @else
                                  <span class="text-muted">—</span>
                                @endif
                              </td>
                              <td>{{ $ticket->created_at?->format('d/m/Y') ?? '—' }}</td>
                              <td>
                                @if ($ticket->poids !== null)
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
                              <td colspan="7" class="text-center text-muted py-5">Aucun ticket enregistré pour cette usine</td>
                            </tr>
                          @endforelse
                        </tbody>
                      </table>
                    </div>
                    @if ($tickets->hasPages())
                      <div class="card-footer pb-0">
                        {{ $tickets->links('pagination::bootstrap-5') }}
                      </div>
                    @endif
                  </div>
                @endif
              @endif
            </div>
            <!-- / Content -->

            <div class="content-backdrop fade"></div>

            @if (($usine ?? null) && ($tickets !== null))
              <div class="modal fade" id="pointTonnageModal" tabindex="-1" aria-labelledby="pointTonnageModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="pointTonnageModalLabel">Imprimer le point tonnage</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <form id="pointTonnageForm" method="get" action="{{ route('verifications.usine.point-tonnage', $usine['id_usine']) }}" target="_blank">
                      <div class="modal-body">
                        <p class="text-muted small mb-3">Filtre sur la <strong>date de réception</strong> (enregistrement local), jour uniquement.</p>
                        <div class="row g-3">
                          <div class="col-md-6">
                            <label class="form-label" for="pt-date-debut">Date début</label>
                            <input type="date" class="form-control" id="pt-date-debut" name="date_debut" value="{{ old('date_debut') }}" />
                          </div>
                          <div class="col-md-6">
                            <label class="form-label" for="pt-date-fin">Date fin</label>
                            <input type="date" class="form-control" id="pt-date-fin" name="date_fin" value="{{ old('date_fin') }}" />
                          </div>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Générer le PDF</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            @endif

            @include('partials.ticket-verify-modal', ['verifyTicketUsineId' => ($usine ?? [])['id_usine'] ?? null])
@endsection

@push('page-scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      @if (session('open_point_tonnage_modal'))
        var m = document.getElementById('pointTonnageModal');
        if (m && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
          bootstrap.Modal.getOrCreateInstance(m).show();
        }
      @endif

      var f = document.getElementById('pointTonnageForm');
      if (f) {
        f.addEventListener('submit', function (e) {
          var d1 = document.getElementById('pt-date-debut');
          var d2 = document.getElementById('pt-date-fin');
          if (d1 && d2 && d1.value && d2.value && d2.value < d1.value) {
            e.preventDefault();
            alert('La date fin doit être après ou égale à la date début.');
          }
        });
      }
    });
  </script>
@endpush
