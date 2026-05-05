@extends('layouts.app')

@section('content')
<div class="container-fluid" style="margin-top: 80px;">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-2 d-none d-md-block bg-light sidebar" style="min-height: calc(100vh - 80px); padding-top: 20px;">
            <div class="sidebar-sticky">
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-2 mb-2 text-muted">
                    <span>Admin</span>
                </h6>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admintorneos') }}">
                            <i class="fas fa-folder-open mr-2"></i> Torneos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admincargarresultados') }}">
                            <i class="fas fa-edit mr-2"></i> Cargar resultados
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('adminjugadores') }}">
                            <i class="fas fa-address-card mr-2"></i> Jugadores
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('adminvivo') }}">
                            <i class="fas fa-video mr-2"></i> Vivo
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('adminfotos') }}">
                            <i class="fas fa-images mr-2"></i> Fotos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('adminranking') }}">
                            <i class="fas fa-trophy mr-2"></i> Ranking
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admincalendario') }}">
                            <i class="fas fa-calendar-alt mr-2"></i> Calendario
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('adminconfig') }}">
                            <i class="fas fa-cog mr-2"></i> Config
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('sponsors.index') }}">
                            <i class="fas fa-ad mr-2"></i> Sponsors
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="{{ route('admin.menu.index') }}">
                            <i class="fas fa-utensils mr-2"></i> Menú
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main role="main" class="col-md-10 ml-sm-auto px-4" style="padding-top: 20px;">
            @yield('admin_content')
        </main>
    </div>
</div>
@endsection
