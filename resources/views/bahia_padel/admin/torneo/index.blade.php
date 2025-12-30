@extends('bahia_padel/admin/plantilla')

@section('title_header','Torneos')

@section('contenedor')
    <style>
        .seccion-oculta {
            display: none !important;
        }
        .seccion-visible {
            display: flex !important;
        }
        .btn-grande {
            font-size: 2rem;
            padding: 1.5rem 3rem;
            font-weight: 600;
            border-radius: 12px;
            min-width: 800px;
            background-color: transparent !important;
            border-width: 3px;
            display: block;
            width: 100%;
            margin-bottom: 1.5rem;
        }
        .btn-primary.btn-grande {
            border-color: #007bff;
            color: #007bff;
        }
        .btn-primary.btn-grande:hover {
            background-color: #007bff !important;
            color: white;
        }
        .btn-success.btn-grande {
            border-color: #28a745;
            color: #28a745;
        }
        .btn-success.btn-grande:hover {
            background-color: #28a745 !important;
            color: white;
        }
    </style>

    <!-- Sección de botones principales -->
    <div id="seccion_botones" class="d-flex justify-content-center align-items-center seccion-visible" style="height: 60vh;">
        <div style="width: 100%; max-width: 800px;">
            <button type="button" class="btn btn-primary btn-grande" onclick="mostrarNuevoTorneo()">
                Nuevo Torneo
            </button>
            <button type="button" class="btn btn-success btn-grande" onclick="mostrarSeleccionarTorneo()">
                Seleccionar Torneo
            </button>
        </div>
    </div>

    <!-- Sección del formulario de nuevo torneo -->
    <div id="seccion_form_nuevo_torneo" class="d-flex justify-content-center align-items-center seccion-oculta" style="height: 60vh;">    
        @include('bahia_padel.admin.torneo.form_nuevo_torneo')
    </div>

    <!-- Sección de selección de torneo -->
    <div id="seccion_seleccionar_torneo" class="d-flex justify-content-center align-items-center seccion-oculta" style="height: 60vh;">    
        @include('bahia_padel.admin.torneo.seleccionar_torneo')
    </div>

    <!-- Scripts JavaScript -->
    <script>
        function mostrarNuevoTorneo() {    
            console.log('Función mostrarNuevoTorneo ejecutada');
            
            // Ocultar sección de botones
            document.getElementById('seccion_botones').className = 'd-flex justify-content-center align-items-center seccion-oculta';
            // Mostrar formulario de nuevo torneo
            document.getElementById('seccion_form_nuevo_torneo').className = 'd-flex justify-content-center align-items-center seccion-visible';
            // Ocultar sección de seleccionar torneo
            document.getElementById('seccion_seleccionar_torneo').className = 'd-flex justify-content-center align-items-center seccion-oculta';
        }

        function mostrarSeleccionarTorneo() {    
            console.log('Función mostrarSeleccionarTorneo ejecutada');
            
            // Ocultar sección de botones
            document.getElementById('seccion_botones').className = 'd-flex justify-content-center align-items-center seccion-oculta';
            // Mostrar sección de seleccionar torneo
            document.getElementById('seccion_seleccionar_torneo').className = 'd-flex justify-content-center align-items-center seccion-visible';
            // Ocultar formulario de nuevo torneo
            document.getElementById('seccion_form_nuevo_torneo').className = 'd-flex justify-content-center align-items-center seccion-oculta';
        }
    </script>
@endsection
