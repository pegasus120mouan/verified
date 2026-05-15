@extends('layout.main')

@section('title')
  Vérifications — Usines
@endsection

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    @include('partials.usines-catalog', [
      'formAction' => route('verifications'),
      'linkRows' => true,
      'searchInputId' => 'search-usine',
      'pageTitle' => 'Liste des usines',
      'pageSubtitle' => 'Sélectionnez une usine : le nombre de tickets validés est indiqué pour chaque site.',
      'ticketCountsByUsine' => $ticketCountsByUsine,
    ])
  </div>
  <div class="content-backdrop fade"></div>
@endsection
