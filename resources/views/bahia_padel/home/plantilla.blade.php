<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="">
  <meta name="author" content="Rodrigo Banegas">

  <title>Bahia Padel</title>
  <link rel="icon" type="image/x-icon" href="{{ asset('bahiapadel/iconos/bahia_padel_ic.png') }}" />

<!-- Custom fonts for this template-->
  <link href="{{ asset('css/all.min.css') }}" rel="stylesheet" type="text/css">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

  <!-- Para que funcione el datatable-->
  <link rel="stylesheet" type="text/css" href="{{asset('datatable/jquery.dataTables.min.css')}}">
  <!-- Custom styles for this template-->
  <link href="css/sb-admin-2.min.css" rel="stylesheet">
  <link href="{{ asset('css/dark-mode.css') }}" rel="stylesheet">

  <!-- Para que funcione ajax-->
  <script src = "https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <script src="{{asset('js/jquery.min.js')}}"> </script>
  <!-- Para que funcione ajax fin-->    
</head>

@include('layouts.bahiapadel_style')

@include('modal.snackbar')
<div id="snackbar"><p id="snackbar_text">Cambios guardados</p></div>


<body>
  <div class="wrapper">

  <nav class="navbar navbar-expand-md custom-header p-2 mb-4">
  <div class="container-fluid">
    <!-- Logo a la izquierda -->
    <a class="navbar-brand d-flex align-items-center" href="#">
      <img class="icono_header header_ic" src="{{ asset('bahiapadel/iconos/bahia_padel_ic.png') }}">
    </a>
    <!-- Botón hamburguesa a la derecha en mobile -->
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <!-- Menú colapsable -->
    <div class="collapse navbar-collapse justify-content-end" id="mainNavbar">
      <ul class="navbar-nav ml-auto menu-blanco">
        <li class="nav-item mx-1">
          <a class="nav-link header_btn" href="#">Home</a>
        </li>
        <li class="nav-item mx-1">
          <a class="nav-link header_btn" href="#">Torneos</a>
        </li>
        <li class="nav-item mx-1">
          <a class="nav-link header_btn" href="#">Categorías</a>
        </li>
        <li class="nav-item mx-1">
          <a class="nav-link header_btn" href="#">Jugadores</a>
        </li>
        <li class="nav-item mx-1">
          <a class="nav-link header_btn" href="#">Vivo</a>
        </li>
        <li class="nav-item mx-1">
          <a class="nav-link header_btn" onclick="toggleDarkMode()" title="Toggle Dark Mode" style="cursor: pointer;">
            <i class="fas fa-moon"></i>
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>
  </div>
    <!-- Todo el contenido de la página aquí -->    
    <main>
      <!-- Begin Page Content -->
      <div class="container-fluid">
        @yield('contenedor')
      </div>
    </main>
    <footer class="sticky-footer">
      <div class="copyright text-center my-auto">
        <span>Copyright &copy; BahiaPadel - REB @nline</span>
      </div>
    </footer>
  </div>
    
<!-- End of Footer -->

  <script type="text/javascript">
      
  function mostrarSnackbar(texto) {    
      var x = document.getElementById("snackbar");
      x.className = "show";
      document.getElementById("snackbar_text").innerHtml = texto;
      setTimeout(function(){ x.className = x.className.replace("show", ""); }, 3000);
  }

  function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
  }

  // Mantener el modo oscuro al recargar la página
  if (localStorage.getItem('darkMode') === 'true') {
    document.body.classList.add('dark-mode');
  }

  </script>
  <!-- Bootstrap core JavaScript-->
  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

  <!-- Core plugin JavaScript-->
  <script src="{{ asset('js/jquery.easing.min.js') }}"></script>

  <!-- Custom scripts for all pages-->
  <script src="js/sb-admin-2.min.js"></script>

  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script type="text/javascript" src="{{asset('datatable/jquery.dataTables.min.js')}}"></script>
</body>

</html>
