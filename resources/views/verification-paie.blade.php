@extends('layout.main')

@section('title')
  Vérification paie — Usines
@endsection

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    @include('partials.usines-catalog', [
      'formAction' => route('verification-paie.index'),
      'usineRouteName' => 'verification-paie.usine',
      'linkRows' => true,
      'searchInputId' => 'search-usine-paie',
      'pageTitle' => 'Vérification paie',
      'pageSubtitle' => 'Sélectionnez une usine pour accéder aux tickets et suivre la paie sur ce site.',
      'ticketCountsByUsine' => $ticketCountsByUsine,
    ])
  </div>
  <div class="content-backdrop fade"></div>
@endsection
