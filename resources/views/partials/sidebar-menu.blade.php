<ul class="menu-inner py-2 app-menu-nav">
  <li class="menu-header small text-uppercase">
    <span class="menu-header-text">Menu principal</span>
  </li>
  <li class="menu-item @if(request()->routeIs('dashboard')) active @endif">
    <a href="{{ route('dashboard') }}" class="menu-link">
      <i class="menu-icon tf-icons bx bx-grid-alt"></i>
      <div class="text-truncate">Tableau de bord</div>
    </a>
  </li>
  <li class="menu-item @if(request()->routeIs('verifications') || request()->routeIs('verifications.usine*')) active @endif">
    <a href="{{ route('verifications') }}" class="menu-link">
      <i class="menu-icon tf-icons bx bx-check-shield"></i>
      <div class="text-truncate">Vérifications</div>
    </a>
  </li>
  <li class="menu-item @if(request()->routeIs('tickets.*') && ! request()->routeIs('tickets-introuvables.*')) active @endif">
    <a href="{{ route('tickets.index') }}" class="menu-link">
      <i class="menu-icon tf-icons bx bx-receipt"></i>
      <div class="text-truncate">Tickets vérifiés</div>
    </a>
  </li>
  <li class="menu-item @if(request()->routeIs('tickets-introuvables.*')) active @endif">
    <a href="{{ route('tickets-introuvables.index') }}" class="menu-link">
      <i class="menu-icon tf-icons bx bx-error-circle"></i>
      <div class="text-truncate">Tickets introuvables</div>
    </a>
  </li>
  <li class="menu-item @if(request()->routeIs('agents.*')) active @endif">
    <a href="{{ route('agents.index') }}" class="menu-link">
      <i class="menu-icon tf-icons bx bx-group"></i>
      <div class="text-truncate">Agents</div>
    </a>
  </li>
  <li class="menu-item @if(request()->routeIs('usines.*')) active @endif">
    <a href="{{ route('usines.index') }}" class="menu-link">
      <i class="menu-icon tf-icons bx bx-buildings"></i>
      <div class="text-truncate">Usines</div>
    </a>
  </li>
</ul>
