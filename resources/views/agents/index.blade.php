@extends('layout.main')

@section('content')
            <div class="container-xxl flex-grow-1 container-p-y">
              @isset($agentStats)
                @include('partials.agent-stats', ['stats' => $agentStats])
              @endisset

              <h4 class="py-3 mb-2 mb-lg-4">Liste des agents</h4>

              @if ($error)
                <div class="alert alert-danger" role="alert">{{ $error }}</div>
              @endif

              <div class="card mb-4">
                <div class="card-body">
                  <form method="get" action="{{ route('agents.index') }}" class="row g-3 align-items-end">
                    <div class="col-md-5 col-lg-4">
                      <label class="form-label" for="search-agent">Rechercher</label>
                      <input
                        type="search"
                        class="form-control"
                        id="search-agent"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Nom, prénom ou n° agent…"
                        autocomplete="off"
                      />
                    </div>
                    <div class="col-md-4 col-lg-3">
                      <label class="form-label" for="id-chef">Chef d’équipe (ID)</label>
                      <input
                        type="number"
                        class="form-control"
                        id="id-chef"
                        name="id_chef"
                        value="{{ $idChef > 0 ? $idChef : '' }}"
                        min="1"
                        placeholder="Optionnel"
                      />
                    </div>
                    <div class="col-md-3 col-lg-5 d-flex flex-wrap gap-2">
                      <button type="submit" class="btn btn-primary">Rechercher</button>
                      <a href="{{ route('agents.index') }}" class="btn btn-outline-secondary">Réinitialiser</a>
                    </div>
                  </form>
                </div>
              </div>

              <div class="card">
                <div class="table-responsive text-nowrap">
                  <table class="table table-hover mb-0">
                    <thead>
                      <tr>
                        <th class="text-white border-0 py-3 ps-4" style="background-color: #696cff; font-weight: 600; letter-spacing: 0.02em;">N° AGENT</th>
                        <th class="text-white border-0 py-3" style="background-color: #696cff; font-weight: 600; letter-spacing: 0.02em;">NOM COMPLET</th>
                        <th class="text-white border-0 py-3" style="background-color: #696cff; font-weight: 600; letter-spacing: 0.02em;">CONTACT</th>
                        <th class="text-white border-0 py-3" style="background-color: #696cff; font-weight: 600; letter-spacing: 0.02em;">CHEF D’ÉQUIPE</th>
                        <th class="text-white border-0 py-3 pe-4" style="background-color: #696cff; font-weight: 600; letter-spacing: 0.02em;">DATE AJOUT</th>
                      </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                      @forelse ($agents as $a)
                        @php
                          $chef = is_array($a['chef_equipe'] ?? null) ? $a['chef_equipe'] : null;
                          $chefNom = $chef['nom_complet'] ?? '—';
                        @endphp
                        <tr>
                          <td class="py-3 ps-4">
                            <span class="fw-medium text-heading">{{ $a['numero_agent'] ?? '—' }}</span>
                            <span class="text-muted small ms-1">#{{ $a['id_agent'] ?? '' }}</span>
                          </td>
                          <td class="py-3">{{ $a['nom_complet'] ?? trim(($a['nom'] ?? '').' '.($a['prenom'] ?? '')) ?: '—' }}</td>
                          <td class="py-3">{{ $a['contact'] ?? '—' }}</td>
                          <td class="py-3">{{ $chefNom }}</td>
                          <td class="py-3 pe-4 text-muted small">{{ $a['date_ajout'] ?? '—' }}</td>
                        </tr>
                      @empty
                        <tr>
                          <td colspan="5" class="py-5 text-center text-muted">
                            @if ($error)
                              Aucune donnée affichée.
                            @else
                              Aucun agent trouvé.
                            @endif
                          </td>
                        </tr>
                      @endforelse
                    </tbody>
                  </table>
                </div>
                @if ($pagination && count($agents) > 0)
                  @php
                    $current = (int) ($pagination['current_page'] ?? 1);
                    $last = (int) ($pagination['last_page'] ?? 1);
                    $total = (int) ($pagination['total'] ?? 0);
                  @endphp
                  <div class="card-footer d-flex flex-column flex-sm-row align-items-center justify-content-between gap-2">
                    <small class="text-muted">{{ $total }} agent(s) au total — page {{ $current }} / {{ max(1, $last) }}</small>
                    @if ($last > 1)
                      <nav class="d-flex gap-2">
                        @if ($current > 1)
                          <a class="btn btn-sm btn-outline-primary" href="{{ route('agents.index', array_merge(request()->query(), ['page' => $current - 1])) }}">Précédent</a>
                        @endif
                        @if ($current < $last)
                          <a class="btn btn-sm btn-outline-primary" href="{{ route('agents.index', array_merge(request()->query(), ['page' => $current + 1])) }}">Suivant</a>
                        @endif
                      </nav>
                    @endif
                  </div>
                @endif
              </div>
            </div>

            <div class="content-backdrop fade"></div>
@endsection
