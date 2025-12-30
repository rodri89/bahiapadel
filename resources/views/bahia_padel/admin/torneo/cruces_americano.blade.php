@extends('bahia_padel/admin/plantilla')

@section('title_header','Cruces Eliminatorios - Torneo Americano')

@section('contenedor')
<style>
    .bracket-container {
        display: flex;
        justify-content: center;
        padding: 20px;
        background: #fff;
        min-height: 100vh;
    }
    
    .bracket-round {
        display: flex;
        flex-direction: column;
        justify-content: space-around;
        min-height: 600px;
        position: relative;
        margin: 0 30px;
    }
    
    .bracket-round-title {
        text-align: center;
        color: #000;
        font-weight: bold;
        font-size: 1.2rem;
        margin-bottom: 20px;
        text-transform: uppercase;
    }
    
    .match-card {
        background: #f8f9fa;
        border: 2px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        margin: 10px 0;
        min-width: 280px;
        position: relative;
    }
    
    .match-card.winner {
        border-color: #28a745;
    }
    
    .player-pair {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 8px;
        margin: 5px 0;
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 5px;
        position: relative;
    }
    
    .player-pair-content {
        display: flex;
        align-items: center;
        flex: 1;
        margin-right: 15px; /* Espacio entre el contenido y el input */
    }
    
    .player-pair-content .badge {
        margin-left: 10px; /* Espacio entre nombres y badge */
        white-space: nowrap; /* Evitar que el badge se corte */
    }
    
    .player-pair-input {
        margin-left: auto;
        width: 70px;
        flex-shrink: 0;
    }
    
    .player-pair-input input {
        width: 100%;
        padding: 5px;
        text-align: center;
        border: 1px solid #ced4da;
        border-radius: 4px;
    }
    
    .player-pair.winner {
        background: #d4edda;
        border-color: #28a745;
    }
    
    .player-pair.winner::after {
        content: '✓';
        position: absolute;
        right: 85px; /* Ajustado para no interferir con el input */
        color: #28a745;
        font-weight: bold;
        font-size: 1.2rem;
    }
    
    .player-images {
        display: flex;
        margin-right: 10px;
    }
    
    .player-images img {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #dee2e6;
        margin-right: -10px;
    }
    
    .player-images img:last-child {
        margin-right: 0;
    }
    
    .player-names {
        flex: 1;
        color: #333;
        font-size: 0.85rem;
    }
    
    .player-names .player-name {
        font-weight: 600;
    }
    
    .score-input {
        display: none; /* Ya no se usa, los inputs están en cada pareja */
    }
    
    .score-display {
        display: none; /* Ya no se usa */
        margin-left: 10px;
    }
    
    .save-btn {
        background: #28a745;
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.9rem;
        margin-top: 10px;
        width: 100%;
    }
    
    .save-btn:hover {
        background: #218838;
    }
    
    /* Snackbar */
    .snackbar {
        visibility: hidden;
        min-width: 250px;
        margin-left: -125px;
        background-color: #28a745;
        color: #fff;
        text-align: center;
        border-radius: 4px;
        padding: 16px;
        position: fixed;
        z-index: 1000;
        left: 50%;
        bottom: 30px;
        font-size: 14px;
    }
    
    .snackbar.show {
        visibility: visible;
        -webkit-animation: fadein 0.5s, fadeout 0.5s 2.5s;
        animation: fadein 0.5s, fadeout 0.5s 2.5s;
    }
    
    @-webkit-keyframes fadein {
        from {bottom: 0; opacity: 0;}
        to {bottom: 30px; opacity: 1;}
    }
    
    @keyframes fadein {
        from {bottom: 0; opacity: 0;}
        to {bottom: 30px; opacity: 1;}
    }
    
    @-webkit-keyframes fadeout {
        from {bottom: 30px; opacity: 1;}
        to {bottom: 0; opacity: 0;}
    }
    
    @keyframes fadeout {
        from {bottom: 30px; opacity: 1;}
        to {bottom: 0; opacity: 0;}
    }
    
    /* Modal de ganadores */
    .modal-ganadores {
        display: none;
        position: fixed;
        z-index: 2000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.7);
        animation: fadeIn 0.3s;
    }
    
    .modal-ganadores.show {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .modal-content-ganadores {
        background-color: #fff;
        padding: 40px;
        border-radius: 15px;
        text-align: center;
        max-width: 700px;
        width: 90%;
        position: relative;
        box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        animation: slideUp 0.5s;
    }
    
    @keyframes fadeIn {
        from {opacity: 0;}
        to {opacity: 1;}
    }
    
    @keyframes slideUp {
        from {
            transform: translateY(50px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    .modal-ganadores h2 {
        color: #28a745;
        font-size: 2rem;
        margin-bottom: 30px;
        font-weight: bold;
    }
    
    .ganadores-fotos {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 20px;
        margin: 30px 0;
    }
    
    .ganador-foto {
        text-align: center;
    }
    
    .ganador-foto img {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #28a745;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    }
    
    .ganador-foto .nombre {
        margin-top: 15px;
        font-size: 1.1rem;
        font-weight: 600;
        color: #333;
    }
    
    .btn-cerrar-modal {
        margin-top: 20px;
        padding: 10px 30px;
        background: #28a745;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1rem;
    }
    
    .btn-cerrar-modal:hover {
        background: #218838;
    }
    
    /* Confetti */
    .confetti {
        position: fixed;
        width: 10px;
        height: 10px;
        background: #f0f;
        top: -10px;
        z-index: 2100;
        animation: confetti-fall linear forwards;
        pointer-events: none;
    }
    
    @keyframes confetti-fall {
        to {
            transform: translateY(100vh) rotate(720deg);
            opacity: 0;
        }
    }
</style>

<div class="bracket-container">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-secondary" id="btn-volver-clasificacion">
                        ← Volver a Clasificación
                    </button>
                    <h2 class="text-center" style="color: #000;">{{ $torneo->nombre ?? 'Torneo' }}</h2>
                    <div style="width: 150px;"></div> <!-- Spacer para centrar el título -->
                </div>
                <input type="hidden" id="torneo_id" value="{{ $torneo->id ?? 0 }}">
            </div>
        </div>
        
        <div class="row">
            <!-- Cuartos de Final -->
            <div class="col-md-4">
                <div class="bracket-round">
                    <div class="bracket-round-title">CUARTOS DE FINAL</div>
                    @foreach($cruces as $index => $cruce)
                        @if($cruce['ronda'] == 'cuartos')
                            @php
                                $jugador1_1 = collect($jugadores)->firstWhere('id', $cruce['pareja_1']['jugador_1']);
                                $jugador1_2 = collect($jugadores)->firstWhere('id', $cruce['pareja_1']['jugador_2']);
                                $jugador2_1 = collect($jugadores)->firstWhere('id', $cruce['pareja_2']['jugador_1']);
                                $jugador2_2 = collect($jugadores)->firstWhere('id', $cruce['pareja_2']['jugador_2']);
                            @endphp
                            <div class="match-card" data-cruce-id="{{ $index }}" data-ronda="cuartos">
                                <!-- Pareja 1 -->
                                <div class="player-pair pareja-cruce" 
                                     data-pareja="1"
                                     data-jugador-1="{{ $cruce['pareja_1']['jugador_1'] }}"
                                     data-jugador-2="{{ $cruce['pareja_1']['jugador_2'] }}">
                                    <div class="player-pair-content">
                                        <div class="player-images">
                                            <img src="{{ asset($jugador1_1->foto ?? 'images/jugador_img.png') }}" alt="{{ $jugador1_1->nombre ?? '' }}">
                                            <img src="{{ asset($jugador1_2->foto ?? 'images/jugador_img.png') }}" alt="{{ $jugador1_2->nombre ?? '' }}">
                                        </div>
                                        <div class="player-names">
                                            <div class="player-name">{{ $jugador1_1->nombre ?? '' }} {{ $jugador1_1->apellido ?? '' }}</div>
                                            <div class="player-name">{{ $jugador1_2->nombre ?? '' }} {{ $jugador1_2->apellido ?? '' }}</div>
                                        </div>
                                        <span class="badge badge-info">{{ $cruce['pareja_1']['zona'] }}{{ $cruce['pareja_1']['posicion'] }}º</span>
                                    </div>
                                    <div class="player-pair-input">
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               data-cruce-id="{{ $index }}"
                                               data-pareja="1"
                                               data-ronda="cuartos"
                                               min="0"
                                               max="99"
                                               placeholder="0">
                                    </div>
                                </div>
                                
                                <!-- Pareja 2 -->
                                <div class="player-pair pareja-cruce" 
                                     data-pareja="2"
                                     data-jugador-1="{{ $cruce['pareja_2']['jugador_1'] }}"
                                     data-jugador-2="{{ $cruce['pareja_2']['jugador_2'] }}">
                                    <div class="player-pair-content">
                                        <div class="player-images">
                                            <img src="{{ asset($jugador2_1->foto ?? 'images/jugador_img.png') }}" alt="{{ $jugador2_1->nombre ?? '' }}">
                                            <img src="{{ asset($jugador2_2->foto ?? 'images/jugador_img.png') }}" alt="{{ $jugador2_2->nombre ?? '' }}">
                                        </div>
                                        <div class="player-names">
                                            <div class="player-name">{{ $jugador2_1->nombre ?? '' }} {{ $jugador2_1->apellido ?? '' }}</div>
                                            <div class="player-name">{{ $jugador2_2->nombre ?? '' }} {{ $jugador2_2->apellido ?? '' }}</div>
                                        </div>
                                        <span class="badge badge-info">{{ $cruce['pareja_2']['zona'] }}{{ $cruce['pareja_2']['posicion'] }}º</span>
                                    </div>
                                    <div class="player-pair-input">
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               data-cruce-id="{{ $index }}"
                                               data-pareja="2"
                                               data-ronda="cuartos"
                                               min="0"
                                               max="99"
                                               placeholder="0">
                                    </div>
                                </div>
                                
                                <!-- Botón guardar -->
                                <div class="text-center mt-2">
                                    <button type="button" 
                                            class="btn btn-primary btn-sm guardar-cruce" 
                                            data-cruce-id="{{ $index }}"
                                            data-ronda="cuartos">
                                        Guardar
                                    </button>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
            
            <!-- Semifinales -->
            <div class="col-md-4">
                <div class="bracket-round" id="semifinales-container">
                    <div class="bracket-round-title">SEMIFINALES</div>
                    <div id="semifinales-content">
                        @foreach($cruces as $index => $cruce)
                            @if($cruce['ronda'] == 'semifinales')
                                @php
                                    $jugador1_1 = collect($jugadores)->firstWhere('id', $cruce['pareja_1']['jugador_1']);
                                    $jugador1_2 = collect($jugadores)->firstWhere('id', $cruce['pareja_1']['jugador_2']);
                                    $jugador2_1 = collect($jugadores)->firstWhere('id', $cruce['pareja_2']['jugador_1']);
                                    $jugador2_2 = collect($jugadores)->firstWhere('id', $cruce['pareja_2']['jugador_2']);
                                @endphp
                                <div class="match-card" data-cruce-id="{{ $cruce['id'] ?? $index }}" data-ronda="semifinales">
                                    <!-- Pareja 1 -->
                                    <div class="player-pair pareja-cruce" 
                                         data-pareja="1"
                                         data-jugador-1="{{ $cruce['pareja_1']['jugador_1'] }}"
                                         data-jugador-2="{{ $cruce['pareja_1']['jugador_2'] }}">
                                        <div class="player-pair-content">
                                            <div class="player-images">
                                                <img src="{{ asset($jugador1_1->foto ?? 'images/jugador_img.png') }}" alt="{{ $jugador1_1->nombre ?? '' }}">
                                                <img src="{{ asset($jugador1_2->foto ?? 'images/jugador_img.png') }}" alt="{{ $jugador1_2->nombre ?? '' }}">
                                            </div>
                                            <div class="player-names">
                                                <div class="player-name">{{ $jugador1_1->nombre ?? '' }} {{ $jugador1_1->apellido ?? '' }}</div>
                                                <div class="player-name">{{ $jugador1_2->nombre ?? '' }} {{ $jugador1_2->apellido ?? '' }}</div>
                                            </div>
                                            @if(isset($cruce['pareja_1']['zona']) && isset($cruce['pareja_1']['posicion']))
                                                <span class="badge badge-info">{{ $cruce['pareja_1']['zona'] }}{{ $cruce['pareja_1']['posicion'] }}º</span>
                                            @endif
                                        </div>
                                        <div class="player-pair-input">
                                            <input type="number" 
                                                   class="form-control resultado-cruce" 
                                                   data-cruce-id="{{ $cruce['id'] ?? $index }}"
                                                   data-pareja="1"
                                                   data-ronda="semifinales"
                                                   min="0"
                                                   max="99"
                                                   placeholder="0">
                                        </div>
                                    </div>
                                    
                                    <!-- Pareja 2 -->
                                    <div class="player-pair pareja-cruce" 
                                         data-pareja="2"
                                         data-jugador-1="{{ $cruce['pareja_2']['jugador_1'] }}"
                                         data-jugador-2="{{ $cruce['pareja_2']['jugador_2'] }}">
                                        <div class="player-pair-content">
                                            <div class="player-images">
                                                <img src="{{ asset($jugador2_1->foto ?? 'images/jugador_img.png') }}" alt="{{ $jugador2_1->nombre ?? '' }}">
                                                <img src="{{ asset($jugador2_2->foto ?? 'images/jugador_img.png') }}" alt="{{ $jugador2_2->nombre ?? '' }}">
                                            </div>
                                            <div class="player-names">
                                                <div class="player-name">{{ $jugador2_1->nombre ?? '' }} {{ $jugador2_1->apellido ?? '' }}</div>
                                                <div class="player-name">{{ $jugador2_2->nombre ?? '' }} {{ $jugador2_2->apellido ?? '' }}</div>
                                            </div>
                                            @if(isset($cruce['pareja_2']['zona']) && isset($cruce['pareja_2']['posicion']))
                                                <span class="badge badge-info">{{ $cruce['pareja_2']['zona'] }}{{ $cruce['pareja_2']['posicion'] }}º</span>
                                            @endif
                                        </div>
                                        <div class="player-pair-input">
                                            <input type="number" 
                                                   class="form-control resultado-cruce" 
                                                   data-cruce-id="{{ $cruce['id'] ?? $index }}"
                                                   data-pareja="2"
                                                   data-ronda="semifinales"
                                                   min="0"
                                                   max="99"
                                                   placeholder="0">
                                        </div>
                                    </div>
                                    
                                    <!-- Botón guardar -->
                                    <div class="text-center mt-2">
                                        <button type="button" 
                                                class="btn btn-primary btn-sm guardar-cruce" 
                                                data-cruce-id="{{ $cruce['id'] ?? $index }}"
                                                data-ronda="semifinales">
                                            Guardar
                                        </button>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
            
            <!-- Final -->
            <div class="col-md-4">
                <div class="bracket-round" id="final-container">
                    <div class="bracket-round-title">FINAL</div>
                    <div id="final-content">
                        @foreach($cruces as $index => $cruce)
                            @if($cruce['ronda'] == 'final')
                                @php
                                    $jugador1_1 = collect($jugadores)->firstWhere('id', $cruce['pareja_1']['jugador_1']);
                                    $jugador1_2 = collect($jugadores)->firstWhere('id', $cruce['pareja_1']['jugador_2']);
                                    $jugador2_1 = collect($jugadores)->firstWhere('id', $cruce['pareja_2']['jugador_1']);
                                    $jugador2_2 = collect($jugadores)->firstWhere('id', $cruce['pareja_2']['jugador_2']);
                                @endphp
                                <div class="match-card" data-cruce-id="{{ $cruce['id'] ?? $index }}" data-ronda="final">
                                    <!-- Pareja 1 -->
                                    <div class="player-pair pareja-cruce" 
                                         data-pareja="1"
                                         data-jugador-1="{{ $cruce['pareja_1']['jugador_1'] }}"
                                         data-jugador-2="{{ $cruce['pareja_1']['jugador_2'] }}">
                                        <div class="player-pair-content">
                                            <div class="player-images">
                                                <img src="{{ asset($jugador1_1->foto ?? 'images/jugador_img.png') }}" alt="{{ $jugador1_1->nombre ?? '' }}">
                                                <img src="{{ asset($jugador1_2->foto ?? 'images/jugador_img.png') }}" alt="{{ $jugador1_2->nombre ?? '' }}">
                                            </div>
                                            <div class="player-names">
                                                <div class="player-name">{{ $jugador1_1->nombre ?? '' }} {{ $jugador1_1->apellido ?? '' }}</div>
                                                <div class="player-name">{{ $jugador1_2->nombre ?? '' }} {{ $jugador1_2->apellido ?? '' }}</div>
                                            </div>
                                            @if(isset($cruce['pareja_1']['zona']) && isset($cruce['pareja_1']['posicion']))
                                                <span class="badge badge-info">{{ $cruce['pareja_1']['zona'] }}{{ $cruce['pareja_1']['posicion'] }}º</span>
                                            @endif
                                        </div>
                                        <div class="player-pair-input">
                                            <input type="number" 
                                                   class="form-control resultado-cruce" 
                                                   data-cruce-id="{{ $cruce['id'] ?? $index }}"
                                                   data-pareja="1"
                                                   data-ronda="final"
                                                   min="0"
                                                   max="99"
                                                   placeholder="0">
                                        </div>
                                    </div>
                                    
                                    <!-- Pareja 2 -->
                                    <div class="player-pair pareja-cruce" 
                                         data-pareja="2"
                                         data-jugador-1="{{ $cruce['pareja_2']['jugador_1'] }}"
                                         data-jugador-2="{{ $cruce['pareja_2']['jugador_2'] }}">
                                        <div class="player-pair-content">
                                            <div class="player-images">
                                                <img src="{{ asset($jugador2_1->foto ?? 'images/jugador_img.png') }}" alt="{{ $jugador2_1->nombre ?? '' }}">
                                                <img src="{{ asset($jugador2_2->foto ?? 'images/jugador_img.png') }}" alt="{{ $jugador2_2->nombre ?? '' }}">
                                            </div>
                                            <div class="player-names">
                                                <div class="player-name">{{ $jugador2_1->nombre ?? '' }} {{ $jugador2_1->apellido ?? '' }}</div>
                                                <div class="player-name">{{ $jugador2_2->nombre ?? '' }} {{ $jugador2_2->apellido ?? '' }}</div>
                                            </div>
                                            @if(isset($cruce['pareja_2']['zona']) && isset($cruce['pareja_2']['posicion']))
                                                <span class="badge badge-info">{{ $cruce['pareja_2']['zona'] }}{{ $cruce['pareja_2']['posicion'] }}º</span>
                                            @endif
                                        </div>
                                        <div class="player-pair-input">
                                            <input type="number" 
                                                   class="form-control resultado-cruce" 
                                                   data-cruce-id="{{ $cruce['id'] ?? $index }}"
                                                   data-pareja="2"
                                                   data-ronda="final"
                                                   min="0"
                                                   max="99"
                                                   placeholder="0">
                                        </div>
                                    </div>
                                    
                                    <!-- Botón guardar -->
                                    <div class="text-center mt-2">
                                        <button type="button" 
                                                class="btn btn-primary btn-sm guardar-cruce" 
                                                data-cruce-id="{{ $cruce['id'] ?? $index }}"
                                                data-ronda="final">
                                            Guardar
                                        </button>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Snackbar -->
<div id="snackbar" class="snackbar">Resultado guardado correctamente</div>

<!-- Modal de Ganadores -->
<div id="modal-ganadores" class="modal-ganadores">
    <div class="modal-content-ganadores">
        <h2>🏆 ¡GANADORES! 🏆</h2>
        <div class="ganadores-fotos" id="ganadores-fotos">
            <!-- Se llenará dinámicamente -->
        </div>
        <button type="button" class="btn-cerrar-modal" onclick="cerrarModalGanadores()">Cerrar</button>
    </div>
</div>

<script type="text/javascript">
    // Función para mostrar snackbar
    function mostrarSnackbar(mensaje) {
        let snackbar = document.getElementById("snackbar");
        snackbar.textContent = mensaje;
        snackbar.className = "snackbar show";
        setTimeout(function(){ snackbar.className = snackbar.className.replace("show", ""); }, 3000);
    }
    
    // Botón volver a clasificación
    $('#btn-volver-clasificacion').on('click', function() {
        let torneoId = $('#torneo_id').val();
        window.location.href = '/admin_torneo_americano_partidos?torneo_id=' + torneoId;
    });
    
    let cruces = @json($cruces ?? []);
    let jugadores = @json($jugadores ?? []);
    let resultadosGuardados = @json($resultadosGuardados ?? []);
    let primerosClasificados = @json($primerosClasificados ?? []);
    let totalClasificados = {{ $totalClasificados ?? 0 }};
    let torneoId = $('#torneo_id').val();
    let resultadosCuartos = {};
    let resultadosSemifinales = {};
    let resultadoFinal = null;
    
    // Cargar resultados guardados al iniciar
    function cargarResultadosGuardados() {
        // Primero cargar resultados de cuartos
        resultadosGuardados.forEach(function(resultado) {
            if (resultado.ronda === 'cuartos') {
                // Buscar el cruce de cuartos que coincida con estas parejas
                let cruceIndex = cruces.findIndex(function(c) {
                    if (c.ronda !== 'cuartos') return false;
                    let p1 = c.pareja_1;
                    let p2 = c.pareja_2;
                    return (p1.jugador_1 == resultado.pareja_1_jugador_1 && p1.jugador_2 == resultado.pareja_1_jugador_2 &&
                            p2.jugador_1 == resultado.pareja_2_jugador_1 && p2.jugador_2 == resultado.pareja_2_jugador_2) ||
                           (p1.jugador_1 == resultado.pareja_2_jugador_1 && p1.jugador_2 == resultado.pareja_2_jugador_2 &&
                            p2.jugador_1 == resultado.pareja_1_jugador_1 && p2.jugador_2 == resultado.pareja_1_jugador_2);
                });
                
                if (cruceIndex !== -1) {
                    // Cargar valores en los inputs
                    $(`.resultado-cruce[data-cruce-id="${cruceIndex}"][data-ronda="cuartos"]`).each(function() {
                        let pareja = $(this).data('pareja');
                        if (pareja == 1) {
                            $(this).val(resultado.pareja_1_set_1);
                        } else if (pareja == 2) {
                            $(this).val(resultado.pareja_2_set_1);
                        }
                    });
                    
                    // Guardar resultado localmente
                    let cruce = cruces[cruceIndex];
                    let ganador = resultado.pareja_1_set_1 > resultado.pareja_2_set_1 ? cruce.pareja_1 : cruce.pareja_2;
                    resultadosCuartos[cruceIndex] = {
                        ganador: ganador,
                        perdedor: resultado.pareja_1_set_1 > resultado.pareja_2_set_1 ? cruce.pareja_2 : cruce.pareja_1,
                        score1: resultado.pareja_1_set_1,
                        score2: resultado.pareja_2_set_1
                    };
                    
                    // Marcar visualmente
                    let matchCard = $(`.match-card[data-cruce-id="${cruceIndex}"]`);
                    matchCard.addClass('winner');
                    if (resultado.pareja_1_set_1 > resultado.pareja_2_set_1) {
                        matchCard.find('.pareja-cruce[data-pareja="1"]').addClass('winner');
                    } else {
                        matchCard.find('.pareja-cruce[data-pareja="2"]').addClass('winner');
                    }
                }
            }
        });
        
        // Actualizar semifinales y final con los resultados guardados de cuartos
        verificarAvance();
        
        // Cargar resultados de semifinales y final después de que se generen
        setTimeout(function() {
            resultadosGuardados.forEach(function(resultado) {
                if (resultado.ronda === 'semifinales' || resultado.ronda === 'final') {
                    // Buscar el cruce que coincida por parejas o por cruce_id
                    let cruce = null;
                    if (resultado.cruce_id) {
                        // Si hay cruce_id, buscar por ID primero
                        cruce = cruces.find(c => c.id === resultado.cruce_id && c.ronda === resultado.ronda);
                    }
                    
                    // Si no se encontró por ID, buscar por parejas
                    if (!cruce) {
                        cruce = cruces.find(function(c) {
                            if (c.ronda !== resultado.ronda) return false;
                            if (!c.pareja_1 || !c.pareja_2) return false;
                            let p1 = c.pareja_1;
                            let p2 = c.pareja_2;
                            return (p1.jugador_1 == resultado.pareja_1_jugador_1 && p1.jugador_2 == resultado.pareja_1_jugador_2 &&
                                    p2.jugador_1 == resultado.pareja_2_jugador_1 && p2.jugador_2 == resultado.pareja_2_jugador_2) ||
                                   (p1.jugador_1 == resultado.pareja_2_jugador_1 && p1.jugador_2 == resultado.pareja_2_jugador_2 &&
                                    p2.jugador_1 == resultado.pareja_1_jugador_1 && p2.jugador_2 == resultado.pareja_1_jugador_2);
                        });
                    }
                    
                    if (cruce) {
                        let cruceId = cruce.id;
                        // Buscar inputs por cruce_id o por parejas
                        $(`.resultado-cruce[data-ronda="${resultado.ronda}"]`).each(function() {
                            let inputCruceId = $(this).data('cruce-id');
                            let pareja = $(this).data('pareja');
                            
                            // Si el cruce_id coincide o si no hay cruce_id pero las parejas coinciden
                            if (inputCruceId === cruceId || (!inputCruceId && cruceId)) {
                                // Verificar que las parejas del input coincidan con el resultado
                                let matchCard = $(this).closest('.match-card');
                                if (matchCard.length > 0) {
                                    if (pareja == 1) {
                                        $(this).val(resultado.pareja_1_set_1);
                                    } else if (pareja == 2) {
                                        $(this).val(resultado.pareja_2_set_1);
                                    }
                                }
                            }
                        });
                        
                        // También buscar por atributo data-cruce-id específico
                        $(`.resultado-cruce[data-cruce-id="${cruceId}"][data-ronda="${resultado.ronda}"]`).each(function() {
                            let pareja = $(this).data('pareja');
                            if (pareja == 1) {
                                $(this).val(resultado.pareja_1_set_1);
                            } else if (pareja == 2) {
                                $(this).val(resultado.pareja_2_set_1);
                            }
                        });
                        
                        // Guardar resultado localmente
                        if (resultado.ronda === 'semifinales') {
                            let ganador = resultado.pareja_1_set_1 > resultado.pareja_2_set_1 ? cruce.pareja_1 : cruce.pareja_2;
                            resultadosSemifinales[cruceId] = {
                                ganador: ganador,
                                perdedor: resultado.pareja_1_set_1 > resultado.pareja_2_set_1 ? cruce.pareja_2 : cruce.pareja_1,
                                score1: resultado.pareja_1_set_1,
                                score2: resultado.pareja_2_set_1
                            };
                            
                            let matchCard = $(`.match-card[data-cruce-id="${cruceId}"], .match-card[data-ronda="semifinales"]`);
                            if (matchCard.length > 0) {
                                matchCard.first().addClass('winner');
                                if (resultado.pareja_1_set_1 > resultado.pareja_2_set_1) {
                                    matchCard.first().find('.player-pair').first().addClass('winner');
                                } else {
                                    matchCard.first().find('.player-pair').last().addClass('winner');
                                }
                            }
                        } else if (resultado.ronda === 'final') {
                            let ganador = resultado.pareja_1_set_1 > resultado.pareja_2_set_1 ? cruce.pareja_1 : cruce.pareja_2;
                            resultadoFinal = {
                                ganador: ganador,
                                perdedor: resultado.pareja_1_set_1 > resultado.pareja_2_set_1 ? cruce.pareja_2 : cruce.pareja_1,
                                score1: resultado.pareja_1_set_1,
                                score2: resultado.pareja_2_set_1
                            };
                            
                            let matchCard = $(`.match-card[data-cruce-id="${cruceId}"], .match-card[data-ronda="final"]`);
                            if (matchCard.length > 0) {
                                matchCard.first().addClass('winner');
                                if (resultado.pareja_1_set_1 > resultado.pareja_2_set_1) {
                                    matchCard.first().find('.player-pair').first().addClass('winner');
                                } else {
                                    matchCard.first().find('.player-pair').last().addClass('winner');
                                }
                            }
                        }
                    }
                }
            });
            
            // Verificar avance después de cargar todos los resultados
            verificarAvance();
        }, 1000);
    }
    
    // Convertir jugadores a objeto keyed por id
    let jugadoresObj = {};
    jugadores.forEach(function(j) {
        jugadoresObj[j.id] = j;
    });
    
    function obtenerJugadorPorId(id) {
        return jugadoresObj[id] || null;
    }
    
    function renderizarPareja(pareja, esGanador = false, cruceId = null, parejaNum = null, ronda = null, valor = '') {
        let jugador1 = obtenerJugadorPorId(pareja.jugador_1);
        let jugador2 = obtenerJugadorPorId(pareja.jugador_2);
        let claseGanador = esGanador ? ' winner' : '';
        let inputHtml = '';
        
        if (cruceId !== null && parejaNum !== null && ronda !== null) {
            inputHtml = `
                <div class="player-pair-input">
                    <input type="number" 
                           class="form-control resultado-cruce" 
                           data-cruce-id="${cruceId}"
                           data-pareja="${parejaNum}"
                           data-ronda="${ronda}"
                           min="0"
                           max="99"
                           placeholder="0"
                           value="${valor}">
                </div>
            `;
        }
        
        let badgeHtml = '';
        if (pareja.zona && pareja.posicion) {
            badgeHtml = `<span class="badge badge-info">${pareja.zona}${pareja.posicion}º</span>`;
        }
        
        return `
            <div class="player-pair${claseGanador}">
                <div class="player-pair-content">
                    <div class="player-images">
                        <img src="/${jugador1 ? (jugador1.foto || 'images/jugador_img.png') : 'images/jugador_img.png'}" alt="${jugador1 ? jugador1.nombre : ''}">
                        <img src="/${jugador2 ? (jugador2.foto || 'images/jugador_img.png') : 'images/jugador_img.png'}" alt="${jugador2 ? jugador2.nombre : ''}">
                    </div>
                    <div class="player-names">
                        <div class="player-name">${jugador1 ? (jugador1.nombre + ' ' + jugador1.apellido) : ''}</div>
                        <div class="player-name">${jugador2 ? (jugador2.nombre + ' ' + jugador2.apellido) : ''}</div>
                    </div>
                    ${badgeHtml}
                </div>
                ${inputHtml}
            </div>
        `;
    }
    
    // Guardar resultado de cruce
    $(document).on('click', '.guardar-cruce', function() {
        let cruceId = $(this).data('cruce-id');
        let ronda = $(this).data('ronda');
        let pareja1Input = $(`.resultado-cruce[data-cruce-id="${cruceId}"][data-pareja="1"]`);
        let pareja2Input = $(`.resultado-cruce[data-cruce-id="${cruceId}"][data-pareja="2"]`);
        
        let pareja1Puntos = parseInt(pareja1Input.val()) || 0;
        let pareja2Puntos = parseInt(pareja2Input.val()) || 0;
        
        if (pareja1Puntos === 0 && pareja2Puntos === 0) {
            mostrarSnackbar('Debe ingresar al menos un resultado');
            return;
        }
        
        // Buscar el cruce correcto según la ronda
        let cruce = null;
        if (ronda === 'cuartos') {
            // Para cuartos, usar el índice numérico
            cruce = cruces[cruceId];
        } else if (ronda === 'semifinales' || ronda === 'final') {
            // Para semifinales y final, buscar por ID
            cruce = cruces.find(c => c.ronda === ronda && c.id === cruceId);
        }
        
        if (!cruce || !cruce.pareja_1 || !cruce.pareja_2) {
            alert('Error: No se encontró el cruce o falta información de las parejas');
            return;
        }
        
        $.ajax({
            type: 'POST',
            dataType: 'JSON',
            url: '/guardar_resultado_cruce_americano',
            data: {
                torneo_id: torneoId,
                ronda: ronda,
                pareja_1_jugador_1: cruce.pareja_1.jugador_1,
                pareja_1_jugador_2: cruce.pareja_1.jugador_2,
                pareja_2_jugador_1: cruce.pareja_2.jugador_1,
                pareja_2_jugador_2: cruce.pareja_2.jugador_2,
                pareja_1_set_1: pareja1Puntos,
                pareja_2_set_1: pareja2Puntos,
                _token: '{{csrf_token()}}'
            },
            success: function(response) {
                if (response.success) {
                    // Guardar resultado localmente
                    if (ronda === 'cuartos') {
                        resultadosCuartos[cruceId] = {
                            ganador: pareja1Puntos > pareja2Puntos ? cruce.pareja_1 : cruce.pareja_2,
                            perdedor: pareja1Puntos > pareja2Puntos ? cruce.pareja_2 : cruce.pareja_1,
                            score1: pareja1Puntos,
                            score2: pareja2Puntos
                        };
                        
                        // Actualizar visualización
                        let matchCard = $(`.match-card[data-cruce-id="${cruceId}"]`);
                        matchCard.addClass('winner');
                        
                        if (pareja1Puntos > pareja2Puntos) {
                            matchCard.find('.pareja-cruce[data-pareja="1"]').addClass('winner');
                            matchCard.find('.pareja-cruce[data-pareja="2"]').removeClass('winner');
                        } else {
                            matchCard.find('.pareja-cruce[data-pareja="2"]').addClass('winner');
                            matchCard.find('.pareja-cruce[data-pareja="1"]').removeClass('winner');
                        }
                        
                        verificarAvance();
                    } else if (ronda === 'semifinales') {
                        resultadosSemifinales[cruceId] = {
                            ganador: pareja1Puntos > pareja2Puntos ? cruce.pareja_1 : cruce.pareja_2,
                            perdedor: pareja1Puntos > pareja2Puntos ? cruce.pareja_2 : cruce.pareja_1,
                            score1: pareja1Puntos,
                            score2: pareja2Puntos
                        };
                        
                        let matchCard = $(`.match-card[data-cruce-id="${cruceId}"]`);
                        matchCard.addClass('winner');
                        
                        if (pareja1Puntos > pareja2Puntos) {
                            matchCard.find('.player-pair').first().addClass('winner');
                            matchCard.find('.player-pair').last().removeClass('winner');
                        } else {
                            matchCard.find('.player-pair').last().addClass('winner');
                            matchCard.find('.player-pair').first().removeClass('winner');
                        }
                        
                        // Actualizar inmediatamente la final
                        verificarAvance();
                    } else if (ronda === 'final') {
                        resultadoFinal = {
                            ganador: pareja1Puntos > pareja2Puntos ? cruce.pareja_1 : cruce.pareja_2,
                            perdedor: pareja1Puntos > pareja2Puntos ? cruce.pareja_2 : cruce.pareja_1,
                            score1: pareja1Puntos,
                            score2: pareja2Puntos
                        };
                        
                        let matchCard = $(`.match-card[data-cruce-id="${cruceId}"]`);
                        matchCard.addClass('winner');
                        
                        if (pareja1Puntos > pareja2Puntos) {
                            matchCard.find('.player-pair').first().addClass('winner');
                            matchCard.find('.player-pair').last().removeClass('winner');
                        } else {
                            matchCard.find('.player-pair').last().addClass('winner');
                            matchCard.find('.player-pair').first().removeClass('winner');
                        }
                        
                        // Mostrar modal de ganadores con confetti
                        mostrarModalGanadores(resultadoFinal.ganador);
                        crearConfetti();
                    }
                    
                    mostrarSnackbar('Resultado guardado correctamente');
                } else {
                    mostrarSnackbar('Error al guardar: ' + (response.message || 'Error desconocido'));
                }
            },
            error: function() {
                mostrarSnackbar('Error al guardar el resultado');
            }
        });
    });
    
    function verificarAvance() {
        // Actualizar semifinales cada vez que se guarda un resultado de cuartos
        actualizarSemifinales();
        
        // Actualizar final cada vez que se guarda un resultado de semifinales
        actualizarFinal();
    }
    
    function actualizarSemifinales() {
        // Obtener ganadores de cuartos en orden (0, 1, 2, 3)
        let ganadoresCuartos = [];
        for (let i = 0; i < 4; i++) {
            if (resultadosCuartos[i] && resultadosCuartos[i].ganador) {
                ganadoresCuartos[i] = resultadosCuartos[i].ganador;
            } else {
                ganadoresCuartos[i] = null;
            }
        }
        
        // Guardar valores actuales de los inputs antes de regenerar
        let valoresGuardados = {};
        $('.resultado-cruce[data-ronda="semifinales"]').each(function() {
            let cruceId = $(this).data('cruce-id');
            let pareja = $(this).data('pareja');
            let valor = $(this).val();
            if (!valoresGuardados[cruceId]) {
                valoresGuardados[cruceId] = {};
            }
            valoresGuardados[cruceId][pareja] = valor;
        });
        
        let html = '';
        
        // Caso especial: 6 clasificados
        // Los primeros pasan directo a semifinales, los ganadores de cuartos juegan contra ellos
        if (totalClasificados === 6 && primerosClasificados.length > 0) {
            // Mostrar semifinales cuando hay ganadores de cuartos o cuando ya hay primeros
            if (ganadoresCuartos.filter(g => g !== null).length >= 1 || primerosClasificados.length > 0) {
                // Crear semifinales: Primer primero vs Ganador cuartos 1, Segundo primero vs Ganador cuartos 2
                for (let i = 0; i < primerosClasificados.length && i < 2; i++) {
                    let primero = primerosClasificados[i];
                    let ganadorCuartos = ganadoresCuartos[i] || null;
                    
                    let sfId = 'sf' + (i + 1);
                    let sf = cruces.find(c => c.ronda === 'semifinales' && c.id === sfId);
                    
                    if (!sf) {
                        sf = {
                            id: sfId,
                            pareja_1: primero,
                            pareja_2: ganadorCuartos,
                            ronda: 'semifinales'
                        };
                        cruces.push(sf);
                    } else {
                        sf.pareja_1 = primero;
                        if (ganadorCuartos) {
                            sf.pareja_2 = ganadorCuartos;
                        }
                    }
                    
                    let valor1 = valoresGuardados[sfId] && valoresGuardados[sfId][1] ? valoresGuardados[sfId][1] : '';
                    let valor2 = valoresGuardados[sfId] && valoresGuardados[sfId][2] ? valoresGuardados[sfId][2] : '';
                    
                    html += `
                        <div class="match-card" data-cruce-id="${sfId}" data-ronda="semifinales">
                            ${renderizarPareja(sf.pareja_1, false, sfId, 1, 'semifinales', valor1)}
                            ${sf.pareja_2 ? renderizarPareja(sf.pareja_2, false, sfId, 2, 'semifinales', valor2) : '<div class="player-pair"><div class="player-pair-content"><div class="player-names">Esperando ganador de cuartos...</div></div></div>'}
                            <div class="text-center mt-2">
                                <button type="button" class="btn btn-primary btn-sm guardar-cruce" data-cruce-id="${sfId}" data-ronda="semifinales" ${!sf.pareja_2 ? 'disabled' : ''}>Guardar</button>
                            </div>
                        </div>
                    `;
                }
            }
        } else {
            // Lógica estándar para 8 o más clasificados
            // Mostrar semifinales cuando hay al menos 1 ganador de cuartos
            let ganadoresCount = ganadoresCuartos.filter(g => g !== null).length;
            if (ganadoresCount >= 1) {
                // Ordenar ganadores según el orden de los cuartos
                // SF1: Ganador QF1 vs Ganador QF3
                // SF2: Ganador QF2 vs Ganador QF4
                let sf1Pareja1 = ganadoresCuartos[0] || null;
                let sf1Pareja2 = ganadoresCuartos[2] || null;
                let sf2Pareja1 = ganadoresCuartos[1] || null;
                let sf2Pareja2 = ganadoresCuartos[3] || null;
                
                // Semifinal 1: Ganador QF1 vs Ganador QF3
                if (sf1Pareja1) {
                    // Buscar si ya existe un cruce de semifinales con estas parejas (viene de la base de datos)
                    let sf1 = cruces.find(c => {
                        if (c.ronda !== 'semifinales') return false;
                        if (!c.pareja_1 || !c.pareja_2) return false;
                        // Verificar si coincide con las parejas esperadas
                        let p1Match = (c.pareja_1.jugador_1 == sf1Pareja1.jugador_1 && c.pareja_1.jugador_2 == sf1Pareja1.jugador_2) ||
                                     (c.pareja_2.jugador_1 == sf1Pareja1.jugador_1 && c.pareja_2.jugador_2 == sf1Pareja1.jugador_2);
                        let p2Match = sf1Pareja2 ? ((c.pareja_1.jugador_1 == sf1Pareja2.jugador_1 && c.pareja_1.jugador_2 == sf1Pareja2.jugador_2) ||
                                                   (c.pareja_2.jugador_1 == sf1Pareja2.jugador_1 && c.pareja_2.jugador_2 == sf1Pareja2.jugador_2)) : false;
                        return p1Match && (sf1Pareja2 ? p2Match : true);
                    });
                    
                    let sf1Id = sf1 ? sf1.id : 'sf1';
                    
                    if (!sf1) {
                        sf1 = {
                            id: sf1Id,
                            pareja_1: sf1Pareja1,
                            pareja_2: sf1Pareja2 || null,
                            ronda: 'semifinales'
                        };
                        cruces.push(sf1);
                    } else {
                        sf1.pareja_1 = sf1Pareja1;
                        if (sf1Pareja2) {
                            sf1.pareja_2 = sf1Pareja2;
                        }
                    }
                    
                    let valor1 = valoresGuardados[sf1Id] && valoresGuardados[sf1Id][1] ? valoresGuardados[sf1Id][1] : '';
                    let valor2 = valoresGuardados[sf1Id] && valoresGuardados[sf1Id][2] ? valoresGuardados[sf1Id][2] : '';
                    
                    html += `
                        <div class="match-card" data-cruce-id="${sf1Id}" data-ronda="semifinales">
                            ${renderizarPareja(sf1.pareja_1, false, sf1Id, 1, 'semifinales', valor1)}
                            ${sf1Pareja2 ? renderizarPareja(sf1.pareja_2, false, sf1Id, 2, 'semifinales', valor2) : '<div class="player-pair"><div class="player-pair-content"><div class="player-names">Esperando ganador...</div></div></div>'}
                            <div class="text-center mt-2">
                                <button type="button" class="btn btn-primary btn-sm guardar-cruce" data-cruce-id="${sf1Id}" data-ronda="semifinales" ${!sf1Pareja2 ? 'disabled' : ''}>Guardar</button>
                            </div>
                        </div>
                    `;
                }
                
                // Semifinal 2: Ganador QF2 vs Ganador QF4
                if (sf2Pareja1) {
                    // Buscar si ya existe un cruce de semifinales con estas parejas (viene de la base de datos)
                    let sf2 = cruces.find(c => {
                        if (c.ronda !== 'semifinales') return false;
                        if (!c.pareja_1 || !c.pareja_2) return false;
                        // Verificar si coincide con las parejas esperadas
                        let p1Match = (c.pareja_1.jugador_1 == sf2Pareja1.jugador_1 && c.pareja_1.jugador_2 == sf2Pareja1.jugador_2) ||
                                     (c.pareja_2.jugador_1 == sf2Pareja1.jugador_1 && c.pareja_2.jugador_2 == sf2Pareja1.jugador_2);
                        let p2Match = sf2Pareja2 ? ((c.pareja_1.jugador_1 == sf2Pareja2.jugador_1 && c.pareja_1.jugador_2 == sf2Pareja2.jugador_2) ||
                                                   (c.pareja_2.jugador_1 == sf2Pareja2.jugador_1 && c.pareja_2.jugador_2 == sf2Pareja2.jugador_2)) : false;
                        return p1Match && (sf2Pareja2 ? p2Match : true);
                    });
                    
                    let sf2Id = sf2 ? sf2.id : 'sf2';
                    
                    if (!sf2) {
                        sf2 = {
                            id: sf2Id,
                            pareja_1: sf2Pareja1,
                            pareja_2: sf2Pareja2 || null,
                            ronda: 'semifinales'
                        };
                        cruces.push(sf2);
                    } else {
                        sf2.pareja_1 = sf2Pareja1;
                        if (sf2Pareja2) {
                            sf2.pareja_2 = sf2Pareja2;
                        }
                    }
                    
                    let valor1 = valoresGuardados[sf2Id] && valoresGuardados[sf2Id][1] ? valoresGuardados[sf2Id][1] : '';
                    let valor2 = valoresGuardados[sf2Id] && valoresGuardados[sf2Id][2] ? valoresGuardados[sf2Id][2] : '';
                    
                    html += `
                        <div class="match-card" data-cruce-id="${sf2Id}" data-ronda="semifinales">
                            ${renderizarPareja(sf2.pareja_1, false, sf2Id, 1, 'semifinales', valor1)}
                            ${sf2Pareja2 ? renderizarPareja(sf2.pareja_2, false, sf2Id, 2, 'semifinales', valor2) : '<div class="player-pair"><div class="player-pair-content"><div class="player-names">Esperando ganador...</div></div></div>'}
                            <div class="text-center mt-2">
                                <button type="button" class="btn btn-primary btn-sm guardar-cruce" data-cruce-id="${sf2Id}" data-ronda="semifinales" ${!sf2Pareja2 ? 'disabled' : ''}>Guardar</button>
                            </div>
                        </div>
                    `;
                }
            }
        }
        
        if (html) {
            $('#semifinales-content').html(html);
            $('#semifinales-container').show();
        }
    }
    
    function actualizarFinal() {
        let ganadoresSemifinales = Object.values(resultadosSemifinales).map(r => r.ganador);
        
        // Guardar valores actuales de los inputs antes de regenerar
        let valoresGuardados = {};
        $('.resultado-cruce[data-ronda="final"]').each(function() {
            let cruceId = $(this).data('cruce-id');
            let pareja = $(this).data('pareja');
            let valor = $(this).val();
            if (!valoresGuardados[cruceId]) {
                valoresGuardados[cruceId] = {};
            }
            valoresGuardados[cruceId][pareja] = valor;
        });
        
        if (ganadoresSemifinales.length >= 1) {
            // Buscar si ya existe un cruce de final con estas parejas (viene de la base de datos)
            let final = cruces.find(c => {
                if (c.ronda !== 'final') return false;
                if (!c.pareja_1 || !c.pareja_2) return false;
                // Verificar si coincide con las parejas esperadas
                let p1Match = (c.pareja_1.jugador_1 == ganadoresSemifinales[0].jugador_1 && c.pareja_1.jugador_2 == ganadoresSemifinales[0].jugador_2) ||
                             (c.pareja_2.jugador_1 == ganadoresSemifinales[0].jugador_1 && c.pareja_2.jugador_2 == ganadoresSemifinales[0].jugador_2);
                let p2Match = ganadoresSemifinales[1] ? ((c.pareja_1.jugador_1 == ganadoresSemifinales[1].jugador_1 && c.pareja_1.jugador_2 == ganadoresSemifinales[1].jugador_2) ||
                                                       (c.pareja_2.jugador_1 == ganadoresSemifinales[1].jugador_1 && c.pareja_2.jugador_2 == ganadoresSemifinales[1].jugador_2)) : false;
                return p1Match && (ganadoresSemifinales[1] ? p2Match : true);
            });
            
            let finalId = final ? final.id : 'final';
            
            if (!final) {
                final = {
                    id: finalId,
                    pareja_1: ganadoresSemifinales[0],
                    pareja_2: ganadoresSemifinales[1] || null,
                    ronda: 'final'
                };
                cruces.push(final);
            } else {
                final.pareja_1 = ganadoresSemifinales[0];
                if (ganadoresSemifinales[1]) {
                    final.pareja_2 = ganadoresSemifinales[1];
                }
            }
            
            let valor1 = valoresGuardados[finalId] && valoresGuardados[finalId][1] ? valoresGuardados[finalId][1] : '';
            let valor2 = valoresGuardados[finalId] && valoresGuardados[finalId][2] ? valoresGuardados[finalId][2] : '';
            
            let html = `
                <div class="match-card" data-cruce-id="${finalId}" data-ronda="final">
                    ${renderizarPareja(final.pareja_1, false, finalId, 1, 'final', valor1)}
                    ${ganadoresSemifinales[1] ? renderizarPareja(final.pareja_2, false, finalId, 2, 'final', valor2) : '<div class="player-pair"><div class="player-pair-content"><div class="player-names">Esperando ganador...</div></div></div>'}
                    <div class="text-center mt-2">
                        <button type="button" class="btn btn-primary btn-sm guardar-cruce" data-cruce-id="${finalId}" data-ronda="final" ${!ganadoresSemifinales[1] ? 'disabled' : ''}>Guardar</button>
                    </div>
                </div>
            `;
            
            $('#final-content').html(html);
            $('#final-container').show();
        }
    }
    
    // Función para mostrar modal de ganadores
    function mostrarModalGanadores(ganador) {
        let jugador1 = obtenerJugadorPorId(ganador.jugador_1);
        let jugador2 = obtenerJugadorPorId(ganador.jugador_2);
        
        let html = '';
        if (jugador1) {
            html += `
                <div class="ganador-foto">
                    <img src="/${jugador1.foto || 'images/jugador_img.png'}" alt="${jugador1.nombre}">
                    <div class="nombre">${jugador1.nombre} ${jugador1.apellido}</div>
                </div>
            `;
        }
        if (jugador2) {
            html += `
                <div class="ganador-foto">
                    <img src="/${jugador2.foto || 'images/jugador_img.png'}" alt="${jugador2.nombre}">
                    <div class="nombre">${jugador2.nombre} ${jugador2.apellido}</div>
                </div>
            `;
        }
        
        $('#ganadores-fotos').html(html);
        $('#modal-ganadores').addClass('show');
    }
    
    // Función para cerrar modal
    function cerrarModalGanadores() {
        $('#modal-ganadores').removeClass('show');
    }
    
    // Función para crear confetti
    function crearConfetti() {
        const colors = ['#ff0000', '#00ff00', '#0000ff', '#ffff00', '#ff00ff', '#00ffff', '#ffa500'];
        const confettiCount = 100;
        
        for (let i = 0; i < confettiCount; i++) {
            setTimeout(() => {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = Math.random() * 100 + '%';
                confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.animationDuration = (Math.random() * 3 + 2) + 's';
                confetti.style.animationDelay = Math.random() * 2 + 's';
                confetti.style.width = (Math.random() * 10 + 5) + 'px';
                confetti.style.height = (Math.random() * 10 + 5) + 'px';
                
                document.body.appendChild(confetti);
                
                setTimeout(() => {
                    confetti.remove();
                }, 5000);
            }, i * 10);
        }
    }
    
    // Cerrar modal al hacer click fuera
    $('#modal-ganadores').on('click', function(e) {
        if (e.target === this) {
            cerrarModalGanadores();
        }
    });
    
    // Inicializar al cargar la página
    $(document).ready(function() {
        // Mostrar contenedores de semifinales y final si hay cruces existentes
        let crucesSemifinales = cruces.filter(c => c.ronda === 'semifinales');
        let crucesFinal = cruces.filter(c => c.ronda === 'final');
        
        // Renderizar cruces de semifinales que vienen de la base de datos
        if (crucesSemifinales.length > 0) {
            $('#semifinales-container').show();
            let htmlSemifinales = '';
            crucesSemifinales.forEach(function(sf) {
                if (sf.pareja_1 && sf.pareja_2) {
                    let sfId = sf.id;
                    let valor1 = '';
                    let valor2 = '';
                    
                    // Buscar resultados guardados para este cruce
                    resultadosGuardados.forEach(function(resultado) {
                        if (resultado.ronda === 'semifinales' && resultado.cruce_id === sfId) {
                            valor1 = resultado.pareja_1_set_1 || '';
                            valor2 = resultado.pareja_2_set_1 || '';
                        }
                    });
                    
                    htmlSemifinales += `
                        <div class="match-card" data-cruce-id="${sfId}" data-ronda="semifinales">
                            ${renderizarPareja(sf.pareja_1, false, sfId, 1, 'semifinales', valor1)}
                            ${renderizarPareja(sf.pareja_2, false, sfId, 2, 'semifinales', valor2)}
                            <div class="text-center mt-2">
                                <button type="button" class="btn btn-primary btn-sm guardar-cruce" data-cruce-id="${sfId}" data-ronda="semifinales">Guardar</button>
                            </div>
                        </div>
                    `;
                }
            });
            if (htmlSemifinales) {
                $('#semifinales-content').html(htmlSemifinales);
            }
        }
        
        // Renderizar cruces de final que vienen de la base de datos
        if (crucesFinal.length > 0) {
            $('#final-container').show();
            let htmlFinal = '';
            crucesFinal.forEach(function(final) {
                if (final.pareja_1 && final.pareja_2) {
                    let finalId = final.id;
                    let valor1 = '';
                    let valor2 = '';
                    
                    // Buscar resultados guardados para este cruce
                    resultadosGuardados.forEach(function(resultado) {
                        if (resultado.ronda === 'final' && resultado.cruce_id === finalId) {
                            valor1 = resultado.pareja_1_set_1 || '';
                            valor2 = resultado.pareja_2_set_1 || '';
                        }
                    });
                    
                    htmlFinal += `
                        <div class="match-card" data-cruce-id="${finalId}" data-ronda="final">
                            ${renderizarPareja(final.pareja_1, false, finalId, 1, 'final', valor1)}
                            ${renderizarPareja(final.pareja_2, false, finalId, 2, 'final', valor2)}
                            <div class="text-center mt-2">
                                <button type="button" class="btn btn-primary btn-sm guardar-cruce" data-cruce-id="${finalId}" data-ronda="final">Guardar</button>
                            </div>
                        </div>
                    `;
                }
            });
            if (htmlFinal) {
                $('#final-content').html(htmlFinal);
            }
        }
        
        cargarResultadosGuardados();
    });
</script>
@endsection
