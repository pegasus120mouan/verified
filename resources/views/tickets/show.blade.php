@extends('layout.main')

@section('content')
            <div class="container-xxl flex-grow-1 container-p-y">
              <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                <div>
                  <h4 class="mb-1">Ticket {{ $ticket->numero_ticket }}</h4>
                  <p class="text-muted small mb-0">
                    Date réception : {{ $ticket->created_at?->format('d/m/Y H:i') ?? '—' }}
                    @if($ticket->updated_at)
                      — Dernière vérification : {{ $ticket->updated_at->format('d/m/Y H:i') }}
                    @endif
                  </p>
                </div>
                <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary">
                  <i class="icon-base bx bx-arrow-back me-1"></i>Retour à la liste
                </a>
              </div>

              <div class="card">
                <div class="card-header"><h5 class="mb-0">Données</h5></div>
                <div class="card-body">
                  <div class="row g-3">
                    <div class="col-md-6"><strong>Numéro ticket</strong><br>{{ $ticket->numero_ticket }}</div>
                    <div class="col-md-6"><strong>Date ticket</strong><br>{{ $ticket->date_ticket?->format('d/m/Y') ?? '—' }}</div>
                    <div class="col-md-6"><strong>Date réception</strong><br>{{ $ticket->created_at?->format('d/m/Y H:i') ?? '—' }}</div>
                    <div class="col-md-6"><strong>Usine</strong><br>
                      @php $nu = $usineNames[$ticket->id_usine] ?? null; @endphp
                      @if($nu)
                        <span class="fw-medium text-uppercase">{{ $nu }}</span>
                      @else
                        <span class="text-muted">—</span>
                      @endif
                    </div>
                    <div class="col-md-6"><strong>Agent</strong><br>
                      @php $na = $agentNames[$ticket->id_agent] ?? null; @endphp
                      @if($na)
                        <span class="fw-medium">{{ $na }}</span>
                      @else
                        <span class="text-muted">—</span>
                      @endif
                    </div>
                    <div class="col-md-6"><strong>Poids</strong><br>@if($ticket->poids !== null){{ number_format((float) $ticket->poids, 0, ',', ' ') }} kg @else — @endif</div>
                    <div class="col-md-6"><strong>Prix unitaire</strong><br>{{ number_format((float) $ticket->prix_unitaire, 2, ',', ' ') }}</div>
                    <div class="col-md-6"><strong>Date validation boss</strong><br>{{ $ticket->date_validation_boss?->format('d/m/Y H:i') ?? '—' }}</div>
                    <div class="col-md-6"><strong>Montant à payer</strong><br>@if($ticket->montant_paie !== null){{ number_format((float) $ticket->montant_paie, 2, ',', ' ') }} @else — @endif</div>
                    <div class="col-md-6"><strong>Montant payé</strong><br>@if($ticket->montant_payer !== null){{ number_format((float) $ticket->montant_payer, 2, ',', ' ') }} @else — @endif</div>
                    <div class="col-md-6"><strong>Reste à payer</strong><br>@if($ticket->montant_reste !== null){{ number_format((float) $ticket->montant_reste, 2, ',', ' ') }} @else — @endif</div>
                    <div class="col-md-6"><strong>Date paiement</strong><br>{{ $ticket->date_paie?->format('d/m/Y H:i') ?? '—' }}</div>
                    <div class="col-md-6"><strong>Statut</strong><br>{{ $ticket->statut_ticket }}</div>
                    <div class="col-md-6"><strong>N° bordereau</strong><br>{{ $ticket->numero_bordereau ?? '—' }}</div>
                  </div>
                </div>
              </div>
            </div>

            <div class="content-backdrop fade"></div>
@endsection
