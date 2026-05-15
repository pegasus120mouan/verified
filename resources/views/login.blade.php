<!doctype html>

<html
  lang="fr"
  class="layout-wide customizer-hide"
  data-assets-path="{{ asset('assets/') }}"
  data-template="vertical-menu-template-free">
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>Connexion | Vérif Ticket</title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon/favicon.ico') }}" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
      rel="stylesheet" />

    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/iconify-icons.css') }}" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/core.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/demo.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/page-auth.css') }}" />

    <script src="{{ asset('assets/vendor/js/helpers.js') }}"></script>
    <script src="{{ asset('assets/js/config.js') }}"></script>
  </head>

  <body>
    <div class="container-xxl">
      <div class="authentication-wrapper authentication-basic container-p-y">
        <div class="authentication-inner">
          <div class="card px-sm-6 px-0">
            <div class="card-body">
              <div class="app-brand justify-content-center mb-4">
                <a href="{{ url('/') }}" class="app-brand-link">
                  @include('partials.app-logo', ['logoHeight' => 88])
                </a>
              </div>

              <h4 class="mb-1">Bienvenue sur votre espace de vérification</h4>
              <p class="mb-6">Connectez-vous à votre compte pour continuer</p>

              <form id="formAuthentication" class="mb-6" method="post" action="{{ route('login.store') }}">
                @csrf
                @if ($errors->any())
                  <div class="alert alert-danger mb-4" role="alert">
                    {{ $errors->first() }}
                  </div>
                @endif

                <div class="mb-6">
                  <label for="login" class="form-label">Identifiant</label>
                  <input
                    type="text"
                    class="form-control @error('login') is-invalid @enderror"
                    id="login"
                    name="login"
                    value="{{ old('login') }}"
                    placeholder="Votre identifiant"
                    autocomplete="username"
                    autofocus />
                  @error('login')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
                </div>

                <div class="mb-6 form-password-toggle">
                  <label class="form-label" for="password">Mot de passe</label>
                  <div class="input-group input-group-merge">
                    <input
                      type="password"
                      id="password"
                      class="form-control"
                      name="password"
                      placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                      autocomplete="current-password"
                      aria-describedby="password" />
                    <span class="input-group-text cursor-pointer"><i class="icon-base bx bx-hide"></i></span>
                  </div>
                </div>

                <div class="mb-8">
                  <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" id="remember-me" name="remember" value="1" />
                    <label class="form-check-label" for="remember-me">Se souvenir de moi</label>
                  </div>
                </div>

                <div class="mb-6">
                  <button class="btn btn-primary d-grid w-100" type="submit">Connexion</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>

    <script src="{{ asset('assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/menu.js') }}"></script>
    <script src="{{ asset('assets/js/main.js') }}"></script>
  </body>
</html>
