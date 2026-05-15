@extends('layout.main')

@section('title')
  Liste des usines
@endsection

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    @include('partials.usines-catalog', [
      'formAction' => route('usines.index'),
      'linkRows' => true,
      'searchInputId' => 'search-usine-catalog',
      'pageTitle' => 'Liste des usines',
      'pageSubtitle' => 'Catalogue Pegasus avec le nombre de tickets validés par usine.',
      'ticketCountsByUsine' => $ticketCountsByUsine,
    ])
  </div>
  <div class="content-backdrop fade"></div>
@endsection
