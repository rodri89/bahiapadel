@extends('bahia_padel/admin/plantilla')

@section('title_header','Validar Cruces - Torneo Americano')

@section('contenedor')
<link rel="stylesheet" href="{{ asset('css/bracket.css') }}">
<link rel="stylesheet" href="{{ asset('css/dark-mode.css') }}">

<style>
    .posiciones-container-scroll {
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
        margin-bottom: 2rem;
        padding-bottom: 0.5rem;
        white-space: nowrap;
    }
    
    .posiciones-container-scroll::-webkit-scrollbar {
        height: 8px;
    }
    
    .posiciones-container-scroll::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    
    .posiciones-container-scroll::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 10px;
    }
    
    .zona-posiciones {
        display: inline-block;
        vertical-align: top;
        min-width: 280px;
        margin-right: 1.5rem;
        flex-shrink: 0;
    }
    
    .posicion-item {
        padding: 0.5rem;
        margin-bottom: 0.5rem;
        border: 1px solid #ddd;
        border-radius: 5px;
        background: #f9f9f9;
    }
    
    .posicion-item.primero {
        background: #d4edda;
        border-color: #28a745;
    }
    
    .posicion-item.segundo {
        background: #d1ecf1;
        border-color: #17a2b8;
    }
    
    .posicion-item.tercero {
        /* Sin estilo por defecto, solo se aplicará amarillo a los mejores */
    }
</style>

<div class="bracket-container">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <button type="button" class="btn btn-secondary" id="btn-volver-resultados">
                        ← Volver a Resultados
                    </button>
                    
                    <h2 class="text-center flex-grow-1 mb-0" style="color: #000;">Validar Cruces - {{ $torneo->nombre ?? 'Torneo' }}</h2>
                    
                    <div class="d-flex align-items-center">
                        <button type="button" class="btn btn-success ml-2" id="btn-confirmar-cruces">
                            <i class="fa fa-check"></i> Confirmar Cruces
                        </button>
                    </div>
                </div>
                <input type="hidden" id="torneo_id" value="{{ $torneo->id ?? 0 }}">
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> 
                    @if($necesitaOctavos ?? false)
                        Revisa los cruces de octavos de final generados. Puedes editar cualquier pareja haciendo clic en los jugadores.
                    @else
                        Revisa los cruces de cuartos de final generados. Puedes editar cualquier pareja haciendo clic en los jugadores.
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Sección de Posiciones por Zona y Tabla de Selección -->
        <div class="row mb-4">
            <!-- Posiciones por Zona (izquierda) -->
            <div class="col-lg-8">
                <h3 class="mb-3">Posiciones por Zona</h3>
                <div class="posiciones-container-scroll">
                    @php
                        $jugadoresMap = [];
                        foreach($jugadores as $j) {
                            $jugadoresMap[$j->id] = $j;
                        }
                    @endphp
                    @foreach($posicionesPorZona ?? [] as $zona => $posiciones)
                        <div class="zona-posiciones">
                            <div class="card">
                                <div class="card-header bg-primary text-white text-center">
                                    <h5 class="mb-0">Zona {{ $zona }}</h5>
                                </div>
                                <div class="card-body">
                                    @foreach($posiciones as $index => $posicion)
                                        @php
                                            $jugador1 = $jugadoresMap[$posicion['jugador_1']] ?? null;
                                            $jugador2 = $jugadoresMap[$posicion['jugador_2']] ?? null;
                                            $clasePosicion = '';
                                            $esMejorTercero = false;
                                            
                                            // Verificar si es uno de los dos mejores terceros
                                            if ($index == 2) {
                                                $terceroId = $zona . '_' . $posicion['jugador_1'] . '_' . $posicion['jugador_2'];
                                                $mejoresTercerosIdsArray = $mejoresTercerosIds ?? [];
                                                $esMejorTercero = in_array($terceroId, $mejoresTercerosIdsArray, true); // strict comparison
                                            }
                                            
                                            if ($index == 0) $clasePosicion = 'primero';
                                            else if ($index == 1) $clasePosicion = 'segundo';
                                            else if ($index == 2) $clasePosicion = 'tercero';
                                            
                                            // Calcular diferencia de games
                                            $diferenciaGames = ($posicion['puntos_ganados'] ?? 0) - ($posicion['puntos_perdidos'] ?? 0);
                                            $diferenciaTexto = $diferenciaGames >= 0 ? '+' . $diferenciaGames : (string)$diferenciaGames;
                                            $diferenciaClass = $diferenciaGames >= 0 ? 'text-success' : 'text-danger';
                                        @endphp
                                        <div class="posicion-item {{ $clasePosicion }}" style="{{ $esMejorTercero ? 'background-color: #fff3cd !important; border-color: #ffc107 !important;' : '' }}">
                                            <div class="d-flex align-items-center">
                                                <span class="badge badge-secondary mr-2" style="font-size: 1rem;">{{ $index + 1 }}º</span>
                                                <div class="flex-grow-1" style="color: #000;">
                                                    @if($jugador1)
                                                        <div><strong style="color: #000;">{{ $jugador1->nombre }} {{ $jugador1->apellido }}</strong></div>
                                                    @endif
                                                    @if($jugador2)
                                                        <div><strong style="color: #000;">{{ $jugador2->nombre }} {{ $jugador2->apellido }}</strong></div>
                                                    @endif
                                                </div>
                                                <div class="text-right">
                                                    <small class="text-muted">
                                                        PG: {{ $posicion['partidos_ganados'] }}<br>
                                                        Pts: {{ $posicion['puntos_ganados'] }}<br>
                                                        <strong class="{{ $diferenciaClass }}">Dif: {{ $diferenciaTexto }}</strong>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            
            <!-- Tabla de selección de cruces a la derecha -->
            <div class="col-lg-4">
                <div class="card shadow bg-white px-4 py-3" style="border-radius: 12px; border: 1px solid #e3e6f0;">
                    <h3 class="text-center mb-4" style="color:#4e73df; font-weight:700;">
                        @if($necesitaOctavos ?? false)
                            Armar Octavos de Final
                        @else
                            Armar Cruces
                        @endif
                    </h3>
                    
                    <!-- Tabla de selección 4x2 o 8x2 según necesite octavos -->
                    <div class="table-responsive" style="max-height: {{ ($necesitaOctavos ?? false) ? '600px' : '400px' }}; overflow-y: auto;">
                        <table class="table table-bordered" style="font-size: 0.9rem;">
                            <thead class="thead-light">
                                <tr>
                                    <th style="text-align: center; width: 50%;">Pareja 1</th>
                                    <th style="text-align: center; width: 50%;">Pareja 2</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $numFilas = ($necesitaOctavos ?? false) ? 8 : 4;
                                @endphp
                                @for($fila = 1; $fila <= $numFilas; $fila++)
                                <tr>
                                    <td style="text-align: center; padding: 8px;">
                                        <select class="form-control form-control-sm select-pareja-cruce" data-fila="{{ $fila }}" data-columna="1">
                                            <option value="">Seleccionar...</option>
                                        </select>
                                    </td>
                                    <td style="text-align: center; padding: 8px;">
                                        <select class="form-control form-control-sm select-pareja-cruce" data-fila="{{ $fila }}" data-columna="2">
                                            <option value="">Seleccionar...</option>
                                        </select>
                                    </td>
                                </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="text-center mt-3">
                        <button type="button" class="btn btn-primary btn-lg" id="btn-armar-cruces">
                            Armar
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            @if($necesitaOctavos ?? false)
            <!-- Octavos de Final -->
            <div class="col-12 mb-4">
                <div class="bracket-round">
                    <div class="bracket-round-title">OCTAVOS DE FINAL</div>
                    <div id="cruces-octavos" class="d-flex flex-wrap justify-content-center">
                        <!-- Se llenará dinámicamente -->
                    </div>
                </div>
            </div>
            @endif
            
            <!-- Cuartos de Final (solo se muestra si no necesita octavos o cuando ya hay cruces de cuartos) -->
            <div class="col-12" @if($necesitaOctavos ?? false) style="display: none;" @endif>
                <div class="bracket-round">
                    <div class="bracket-round-title">CUARTOS DE FINAL</div>
                    <div id="cruces-cuartos" class="d-flex flex-wrap justify-content-center">
                        @foreach($cruces as $index => $cruce)
                            @if($cruce['ronda'] == 'cuartos')
                                @php
                                    $jugador1_1 = collect($jugadores)->firstWhere('id', $cruce['pareja_1']['jugador_1']);
                                    $jugador1_2 = collect($jugadores)->firstWhere('id', $cruce['pareja_1']['jugador_2']);
                                    $jugador2_1 = collect($jugadores)->firstWhere('id', $cruce['pareja_2']['jugador_1']);
                                    $jugador2_2 = collect($jugadores)->firstWhere('id', $cruce['pareja_2']['jugador_2']);
                                @endphp
                                <div class="match-card cruce-editable" data-cruce-index="{{ $index }}" data-ronda="cuartos">
                                    <!-- Pareja 1 -->
                                    <div class="player-pair pareja-editable" 
                                         data-pareja="1"
                                         data-cruce-index="{{ $index }}"
                                         style="cursor: pointer;">
                                        <div class="player-pair-content">
                                            <div class="player-images">
                                                <img src="{{ asset($jugador1_1->foto ?? 'images/jugador_img.png') }}" 
                                                     alt="{{ $jugador1_1->nombre ?? '' }}"
                                                     style="pointer-events: none;">
                                                <img src="{{ asset($jugador1_2->foto ?? 'images/jugador_img.png') }}" 
                                                     alt="{{ $jugador1_2->nombre ?? '' }}"
                                                     style="pointer-events: none;">
                                            </div>
                                            <div class="player-names" style="color: #000;">
                                                <div class="player-name" style="color: #000;">{{ $jugador1_1->nombre ?? '' }} {{ $jugador1_1->apellido ?? '' }}</div>
                                                <div class="player-name" style="color: #000;">{{ $jugador1_2->nombre ?? '' }} {{ $jugador1_2->apellido ?? '' }}</div>
                                            </div>
                                            @if(isset($cruce['pareja_1']['zona']) && isset($cruce['pareja_1']['posicion']))
                                                <span class="badge badge-info">{{ $cruce['pareja_1']['zona'] }}{{ $cruce['pareja_1']['posicion'] }}º</span>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="text-center my-2">
                                        <span style="font-size: 1.5rem; font-weight: bold;">VS</span>
                                    </div>
                                    
                                    <!-- Pareja 2 -->
                                    <div class="player-pair pareja-editable" 
                                         data-pareja="2"
                                         data-cruce-index="{{ $index }}"
                                         style="cursor: pointer;">
                                        <div class="player-pair-content">
                                            <div class="player-images">
                                                <img src="{{ asset($jugador2_1->foto ?? 'images/jugador_img.png') }}" 
                                                     alt="{{ $jugador2_1->nombre ?? '' }}"
                                                     style="pointer-events: none;">
                                                <img src="{{ asset($jugador2_2->foto ?? 'images/jugador_img.png') }}" 
                                                     alt="{{ $jugador2_2->nombre ?? '' }}"
                                                     style="pointer-events: none;">
                                            </div>
                                            <div class="player-names" style="color: #000;">
                                                <div class="player-name" style="color: #000;">{{ $jugador2_1->nombre ?? '' }} {{ $jugador2_1->apellido ?? '' }}</div>
                                                <div class="player-name" style="color: #000;">{{ $jugador2_2->nombre ?? '' }} {{ $jugador2_2->apellido ?? '' }}</div>
                                            </div>
                                            @if(isset($cruce['pareja_2']['zona']) && isset($cruce['pareja_2']['posicion']))
                                                <span class="badge badge-info">{{ $cruce['pareja_2']['zona'] }}{{ $cruce['pareja_2']['posicion'] }}º</span>
                                            @endif
                                        </div>
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

<!-- Modal Seleccionar Pareja -->
<div class="modal fade body_admin" id="modalSeleccionarPareja" tabindex="-1" role="dialog" aria-labelledby="modalSeleccionarParejaLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalSeleccionarParejaLabel">Seleccionar Pareja</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <!-- Buscador -->
        <input type="text" class="form-control mb-3" id="buscador-pareja" placeholder="Buscar pareja por nombre o zona...">
        
        <div class="list-group" id="lista-parejas" style="max-height: 500px; overflow-y: auto;">
          <!-- Las parejas se cargarán dinámicamente -->
        </div>
      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
    var cruces = @json($cruces);
    var necesitaOctavos = {{ ($necesitaOctavos ?? false) ? 'true' : 'false' }};
    
    // Asegurar que los cruces existentes tengan la ronda correcta
    // Si necesitamos octavos y hay cruces sin ronda o con ronda 'cuartos', verificar si deberían ser octavos
    if (necesitaOctavos) {
        cruces.forEach(function(cruce, index) {
            // Si el cruce no tiene ronda o tiene ronda 'cuartos', pero debería ser octavos
            // Verificar si hay 8 cruces (octavos) o 4 cruces (cuartos)
            if (!cruce.ronda || cruce.ronda === 'cuartos') {
                // Si hay más de 4 cruces, los primeros 8 deberían ser octavos
                if (cruces.length > 4 && index < 8) {
                    cruce.ronda = 'octavos';
                }
            }
        });
    }
    var jugadores = @json($jugadores);
    var posicionesPorZona = @json($posicionesPorZona);
    var torneoId = $('#torneo_id').val();
    var parejaSeleccionada = null; // { pareja: 1 o 2, cruceIndex: índice del cruce }
    
    // Preparar datos de posiciones para JavaScript (formato: posiciones[zona][posicion])
    var posicionesJS = {};
    Object.keys(posicionesPorZona).forEach(function(zona) {
        posicionesJS[zona] = {};
        posicionesPorZona[zona].forEach(function(pareja, index) {
            posicionesJS[zona][index + 1] = {
                jugador_1: pareja.jugador_1,
                jugador_2: pareja.jugador_2,
                zona: zona,
                posicion: index + 1
            };
        });
    });
    
    // Crear mapa de jugadores
    var jugadoresMap = {};
    jugadores.forEach(function(j) {
        jugadoresMap[j.id] = j;
    });
    
    // Poblar los selectores con opciones dinámicas
    function poblarSelectoresParejas() {
        var opcionesHTML = '<option value="">Seleccionar...</option>';
        
        // Recorrer todas las zonas y posiciones
        for (var zona in posicionesJS) {
            for (var pos in posicionesJS[zona]) {
                var pareja = posicionesJS[zona][pos];
                var jugador1 = jugadoresMap[pareja.jugador_1] || {};
                var jugador2 = jugadoresMap[pareja.jugador_2] || {};
                var nombrePareja = (jugador1.nombre || '') + ' ' + (jugador1.apellido || '') + ' / ' + 
                                   (jugador2.nombre || '') + ' ' + (jugador2.apellido || '');
                var valor = zona + '_' + pos;
                var texto = pos + zona + ' - ' + nombrePareja;
                opcionesHTML += '<option value="' + valor + '">' + texto + '</option>';
            }
        }
        
        // Aplicar a todos los selectores
        $('.select-pareja-cruce').html(opcionesHTML);
    }
    
    // Llamar a poblar selectores al cargar
    poblarSelectoresParejas();
    
    // Función para generar cruces desde la tabla
    function generarCrucesDesdeTabla() {
        var crucesTemp = [];
        
        // Determinar si necesitamos octavos de final
        var necesitaOctavos = {{ ($necesitaOctavos ?? false) ? 'true' : 'false' }};
        var numFilas = necesitaOctavos ? 8 : 4;
        var ronda = necesitaOctavos ? 'octavos' : 'cuartos';
        
        // Leer las selecciones de la tabla (4x2 o 8x2 según corresponda)
        // Cada fila es un cruce: pareja1 (columna 1) vs pareja2 (columna 2)
        
        for (var fila = 1; fila <= numFilas; fila++) {
            var pareja1Select = $('.select-pareja-cruce[data-fila="' + fila + '"][data-columna="1"]');
            var pareja2Select = $('.select-pareja-cruce[data-fila="' + fila + '"][data-columna="2"]');
            
            var valor1 = pareja1Select.val();
            var valor2 = pareja2Select.val();
            
            if (valor1 && valor2) {
                var partes1 = valor1.split('_');
                var partes2 = valor2.split('_');
                var zona1 = partes1[0];
                var pos1 = parseInt(partes1[1]);
                var zona2 = partes2[0];
                var pos2 = parseInt(partes2[1]);
                
                var pareja1Data = posicionesJS[zona1][pos1];
                var pareja2Data = posicionesJS[zona2][pos2];
                
                crucesTemp.push({
                    id: 'cruce_manual_' + fila,
                    ronda: ronda,
                    pareja_1: {
                        jugador_1: pareja1Data.jugador_1,
                        jugador_2: pareja1Data.jugador_2,
                        zona: zona1,
                        posicion: pos1
                    },
                    pareja_2: {
                        jugador_1: pareja2Data.jugador_1,
                        jugador_2: pareja2Data.jugador_2,
                        zona: zona2,
                        posicion: pos2
                    }
                });
            }
        }
        
        return crucesTemp;
    }
    
    // Función para renderizar cruces en el contenedor de octavos o cuartos
    function renderizarCrucesEnCuartos(crucesData, esOctavos) {
        esOctavos = esOctavos || false;
        var container = esOctavos ? $('#cruces-octavos') : $('#cruces-cuartos');
        var ronda = esOctavos ? 'octavos' : 'cuartos';
        container.empty();
        
        if (!crucesData || crucesData.length === 0) {
            container.html('<p class="text-center text-muted">No hay cruces para mostrar. Selecciona parejas en la tabla superior.</p>');
            return;
        }
        
        crucesData.forEach(function(cruce, index) {
            var jugador1_1 = jugadoresMap[cruce.pareja_1.jugador_1] || null;
            var jugador1_2 = jugadoresMap[cruce.pareja_1.jugador_2] || null;
            var jugador2_1 = jugadoresMap[cruce.pareja_2.jugador_1] || null;
            var jugador2_2 = jugadoresMap[cruce.pareja_2.jugador_2] || null;
            
            var cruceHTML = `
                <div class="match-card cruce-editable" data-cruce-index="${index}" data-ronda="${ronda}">
                    <!-- Pareja 1 -->
                    <div class="player-pair pareja-editable" 
                         data-pareja="1"
                         data-cruce-index="${index}"
                         style="cursor: pointer;">
                        <div class="player-pair-content">
                            <div class="player-images">
                                ${jugador1_1 ? '<img src="{{ asset("") }}' + (jugador1_1.foto || 'images/jugador_img.png') + '" alt="' + jugador1_1.nombre + ' ' + jugador1_1.apellido + '" style="pointer-events: none;">' : ''}
                                ${jugador1_2 ? '<img src="{{ asset("") }}' + (jugador1_2.foto || 'images/jugador_img.png') + '" alt="' + jugador1_2.nombre + ' ' + jugador1_2.apellido + '" style="pointer-events: none;">' : ''}
                            </div>
                            <div class="player-names" style="color: #000;">
                                ${jugador1_1 ? '<div class="player-name" style="color: #000;">' + jugador1_1.nombre + ' ' + jugador1_1.apellido + '</div>' : ''}
                                ${jugador1_2 ? '<div class="player-name" style="color: #000;">' + jugador1_2.nombre + ' ' + jugador1_2.apellido + '</div>' : ''}
                            </div>
                            <span class="badge badge-info">${cruce.pareja_1.zona}${cruce.pareja_1.posicion}º</span>
                        </div>
                    </div>
                    
                    <div class="text-center my-2">
                        <span style="font-size: 1.5rem; font-weight: bold;">VS</span>
                    </div>
                    
                    <!-- Pareja 2 -->
                    <div class="player-pair pareja-editable" 
                         data-pareja="2"
                         data-cruce-index="${index}"
                         style="cursor: pointer;">
                        <div class="player-pair-content">
                            <div class="player-images">
                                ${jugador2_1 ? '<img src="{{ asset("") }}' + (jugador2_1.foto || 'images/jugador_img.png') + '" alt="' + jugador2_1.nombre + ' ' + jugador2_1.apellido + '" style="pointer-events: none;">' : ''}
                                ${jugador2_2 ? '<img src="{{ asset("") }}' + (jugador2_2.foto || 'images/jugador_img.png') + '" alt="' + jugador2_2.nombre + ' ' + jugador2_2.apellido + '" style="pointer-events: none;">' : ''}
                            </div>
                            <div class="player-names" style="color: #000;">
                                ${jugador2_1 ? '<div class="player-name" style="color: #000;">' + jugador2_1.nombre + ' ' + jugador2_1.apellido + '</div>' : ''}
                                ${jugador2_2 ? '<div class="player-name" style="color: #000;">' + jugador2_2.nombre + ' ' + jugador2_2.apellido + '</div>' : ''}
                            </div>
                            <span class="badge badge-info">${cruce.pareja_2.zona}${cruce.pareja_2.posicion}º</span>
                        </div>
                    </div>
                </div>
            `;
            
            container.append(cruceHTML);
        });
        
        // Actualizar el array de cruces global
        cruces = crucesData;
    }
    
    // Botón Armar Cruces
    $('#btn-armar-cruces').on('click', function() {
        var crucesNuevos = generarCrucesDesdeTabla();
        if (crucesNuevos.length === 0) {
            alert('Por favor, selecciona al menos un cruce completo (pareja 1 y pareja 2 en la misma fila)');
            return;
        }
        var necesitaOctavos = {{ ($necesitaOctavos ?? false) ? 'true' : 'false' }};
        var crucesOctavos = crucesNuevos.filter(function(c) { return c.ronda === 'octavos'; });
        var crucesCuartos = crucesNuevos.filter(function(c) { return c.ronda === 'cuartos'; });
        
        if (necesitaOctavos && crucesOctavos.length > 0) {
            renderizarCrucesEnCuartos(crucesOctavos, true);
        }
        if (crucesCuartos.length > 0) {
            renderizarCrucesEnCuartos(crucesCuartos, false);
        }
    });
    
    // Construir lista de todas las parejas disponibles
    function construirListaParejas() {
        var todasLasParejas = [];
        var jugadoresMap = {};
        
        // Crear mapa de jugadores para acceso rápido
        jugadores.forEach(function(j) {
            jugadoresMap[j.id] = j;
        });
        
        // Recorrer todas las zonas y posiciones
        Object.keys(posicionesPorZona).forEach(function(zona) {
            posicionesPorZona[zona].forEach(function(posicion, index) {
                var jugador1 = jugadoresMap[posicion.jugador_1];
                var jugador2 = jugadoresMap[posicion.jugador_2];
                
                if (jugador1 && jugador2) {
                    todasLasParejas.push({
                        zona: zona,
                        posicion: index + 1,
                        jugador_1: posicion.jugador_1,
                        jugador_2: posicion.jugador_2,
                        jugador1_nombre: jugador1.nombre + ' ' + jugador1.apellido,
                        jugador2_nombre: jugador2.nombre + ' ' + jugador2.apellido,
                        jugador1_foto: jugador1.foto || 'images/jugador_img.png',
                        jugador2_foto: jugador2.foto || 'images/jugador_img.png',
                        partidos_ganados: posicion.partidos_ganados || 0,
                        puntos_ganados: posicion.puntos_ganados || 0
                    });
                }
            });
        });
        
        return todasLasParejas;
    }
    
    var todasLasParejas = construirListaParejas();
    
    // Función para renderizar la lista de parejas
    function renderizarListaParejas(parejas) {
        var lista = $('#lista-parejas');
        lista.empty();
        
        if (parejas.length === 0) {
            lista.html('<div class="list-group-item text-center text-muted">No se encontraron parejas</div>');
            return;
        }
        
        parejas.forEach(function(pareja) {
            var item = $('<button>')
                .attr('type', 'button')
                .addClass('list-group-item list-group-item-action pareja-option')
                .css({
                    'display': 'flex',
                    'align-items': 'center',
                    'padding': '1rem',
                    'border': '1px solid #ddd',
                    'margin-bottom': '0.5rem',
                    'border-radius': '5px',
                    'cursor': 'pointer'
                })
                .data('pareja', pareja);
            
            var contenido = $('<div>').css({
                'display': 'flex',
                'align-items': 'center',
                'width': '100%'
            });
            
            // Imágenes de los jugadores
            var imagenes = $('<div>').css({
                'display': 'flex',
                'margin-right': '1rem'
            });
            
            var img1Src = pareja.jugador1_foto && pareja.jugador1_foto !== 'images/jugador_img.png' 
                ? '{{ asset("") }}' + pareja.jugador1_foto 
                : '{{ asset("images/jugador_img.png") }}';
            var img2Src = pareja.jugador2_foto && pareja.jugador2_foto !== 'images/jugador_img.png' 
                ? '{{ asset("") }}' + pareja.jugador2_foto 
                : '{{ asset("images/jugador_img.png") }}';
            
            var img1 = $('<img>')
                .attr('src', img1Src)
                .addClass('rounded-circle')
                .css({
                    'width': '50px',
                    'height': '50px',
                    'object-fit': 'cover',
                    'margin-right': '5px'
                });
            
            var img2 = $('<img>')
                .attr('src', img2Src)
                .addClass('rounded-circle')
                .css({
                    'width': '50px',
                    'height': '50px',
                    'object-fit': 'cover'
                });
            
            imagenes.append(img1).append(img2);
            
            // Información de la pareja
            var info = $('<div>').css({
                'flex-grow': '1'
            });
            
            var nombres = $('<div>').css({
                'font-weight': 'bold',
                'color': '#000',
                'margin-bottom': '0.25rem'
            }).text(pareja.jugador1_nombre + ' / ' + pareja.jugador2_nombre);
            
            var badge = $('<span>')
                .addClass('badge badge-info')
                .text(pareja.zona + pareja.posicion + 'º');
            
            var stats = $('<small>')
                .addClass('text-muted d-block mt-1')
                .text('PG: ' + pareja.partidos_ganados + ' | Pts: ' + pareja.puntos_ganados);
            
            info.append(nombres).append(badge).append(stats);
            
            contenido.append(imagenes).append(info);
            item.append(contenido);
            lista.append(item);
        });
    }
    
    // Inicializar lista
    renderizarListaParejas(todasLasParejas);
    
    // Al hacer clic en una pareja, abrir modal
    $(document).on('click', '.pareja-editable', function(e) {
        e.stopPropagation();
        var pareja = $(this).data('pareja');
        var cruceIndex = $(this).data('cruce-index');
        
        parejaSeleccionada = {
            pareja: pareja,
            cruceIndex: cruceIndex
        };
        
        $('#modalSeleccionarPareja').modal('show');
    });
    
    // Buscador de parejas
    $('#buscador-pareja').on('keyup', function() {
        var filtro = $(this).val().toLowerCase();
        var parejasFiltradas = todasLasParejas.filter(function(pareja) {
            var texto = pareja.jugador1_nombre + ' ' + pareja.jugador2_nombre + ' ' + pareja.zona;
            return texto.toLowerCase().includes(filtro);
        });
        renderizarListaParejas(parejasFiltradas);
    });
    
    // Al seleccionar una pareja del modal
    $(document).on('click', '.pareja-option', function() {
        if (!parejaSeleccionada) return;
        
        var parejaData = $(this).data('pareja');
        var pareja = parejaSeleccionada.pareja;
        var cruceIndex = parejaSeleccionada.cruceIndex;
        
        // Obtener datos de los jugadores
        var jugador1 = jugadores.find(j => j.id == parejaData.jugador_1);
        var jugador2 = jugadores.find(j => j.id == parejaData.jugador_2);
        
        if (jugador1 && jugador2 && cruces[cruceIndex]) {
            // Actualizar el array de cruces
            cruces[cruceIndex]['pareja_' + pareja] = {
                jugador_1: parejaData.jugador_1,
                jugador_2: parejaData.jugador_2,
                zona: parejaData.zona,
                posicion: parejaData.posicion
            };
            
            // Actualizar el DOM
            var parejaElement = $('.pareja-editable[data-pareja="' + pareja + '"][data-cruce-index="' + cruceIndex + '"]');
            var imagenes = parejaElement.find('.player-images img');
            var nombres = parejaElement.find('.player-names .player-name');
            var badge = parejaElement.find('.badge');
            
            // Actualizar imágenes
            var img1Src = jugador1.foto && jugador1.foto !== 'images/jugador_img.png' 
                ? '{{ asset("") }}' + jugador1.foto 
                : '{{ asset("images/jugador_img.png") }}';
            var img2Src = jugador2.foto && jugador2.foto !== 'images/jugador_img.png' 
                ? '{{ asset("") }}' + jugador2.foto 
                : '{{ asset("images/jugador_img.png") }}';
            
            imagenes.eq(0).attr('src', img1Src).attr('alt', jugador1.nombre + ' ' + jugador1.apellido);
            imagenes.eq(1).attr('src', img2Src).attr('alt', jugador2.nombre + ' ' + jugador2.apellido);
            
            // Actualizar nombres
            nombres.eq(0).text(jugador1.nombre + ' ' + jugador1.apellido);
            nombres.eq(1).text(jugador2.nombre + ' ' + jugador2.apellido);
            
            // Actualizar badge
            if (badge.length) {
                badge.text(parejaData.zona + parejaData.posicion + 'º');
            } else {
                parejaElement.find('.player-names').after('<span class="badge badge-info">' + parejaData.zona + parejaData.posicion + 'º</span>');
            }
        }
        
        $('#modalSeleccionarPareja').modal('hide');
        $('#buscador-pareja').val(''); // Limpiar buscador
        renderizarListaParejas(todasLasParejas); // Restaurar lista completa
        parejaSeleccionada = null;
    });
    
    // Limpiar buscador cuando se cierra el modal
    $('#modalSeleccionarPareja').on('hidden.bs.modal', function() {
        $('#buscador-pareja').val('');
        renderizarListaParejas(todasLasParejas);
        parejaSeleccionada = null;
    });
    
    // Botón volver a resultados
    $('#btn-volver-resultados').on('click', function() {
        window.location.href = '{{ route("admintorneoresultados") }}?torneo_id=' + torneoId;
    });
    
    // Botón confirmar cruces
    $('#btn-confirmar-cruces').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true).text('Guardando...');
        
        // Preparar datos de cruces para enviar
        var necesitaOctavos = {{ ($necesitaOctavos ?? false) ? 'true' : 'false' }};
        var crucesParaEnviar = cruces.map(function(cruce, index) {
            // Validar que el cruce tenga las parejas necesarias
            if (!cruce.pareja_1 || !cruce.pareja_2) {
                console.error('Cruce ' + index + ' no tiene pareja_1 o pareja_2:', cruce);
                return null;
            }
            
            if (!cruce.pareja_1.jugador_1 || !cruce.pareja_1.jugador_2 || 
                !cruce.pareja_2.jugador_1 || !cruce.pareja_2.jugador_2) {
                console.error('Cruce ' + index + ' no tiene todos los jugadores:', cruce);
                return null;
            }
            
            // Determinar la ronda correcta
            var ronda = cruce.ronda;
            if (!ronda) {
                // Si no tiene ronda, determinar según si necesitamos octavos
                if (necesitaOctavos) {
                    // Si hay 8 cruces, todos son octavos
                    ronda = (cruces.length === 8) ? 'octavos' : 'cuartos';
                } else {
                    ronda = 'cuartos';
                }
            }
            
            // Si necesitamos octavos y hay 8 cruces, asegurar que todos sean octavos
            if (necesitaOctavos && cruces.length === 8 && ronda === 'cuartos') {
                ronda = 'octavos';
            }
            
            return {
                ronda: ronda,
                pareja_1: {
                    jugador_1: parseInt(cruce.pareja_1.jugador_1),
                    jugador_2: parseInt(cruce.pareja_1.jugador_2),
                    zona: cruce.pareja_1.zona || null,
                    posicion: cruce.pareja_1.posicion || null
                },
                pareja_2: {
                    jugador_1: parseInt(cruce.pareja_2.jugador_1),
                    jugador_2: parseInt(cruce.pareja_2.jugador_2),
                    zona: cruce.pareja_2.zona || null,
                    posicion: cruce.pareja_2.posicion || null
                }
            };
        }).filter(function(cruce) {
            return cruce !== null;
        });
        
        console.log('Cruces a enviar:', crucesParaEnviar);
        
        if (crucesParaEnviar.length === 0) {
            alert('No hay cruces válidos para guardar. Por favor, verifique que todas las parejas estén completas.');
            btn.prop('disabled', false).text('Confirmar Cruces');
            return;
        }
        
        $.ajax({
            type: 'POST',
            url: '{{ route("guardarcruceseditados") }}',
            data: {
                torneo_id: torneoId,
                cruces: crucesParaEnviar,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                console.log('Respuesta del servidor:', response);
                if (response.success) {
                    alert('Cruces confirmados correctamente');
                    // Verificar el tipo de torneo para redirigir apropiadamente
                    var tipoTorneo = '{{ $tipoTorneo ?? "puntuable" }}';
                    if (tipoTorneo === 'puntuable') {
                        window.location.href = '{{ route("admintorneopuntuablecrucesv2") }}?torneo_id=' + torneoId;
                    } else {
                        window.location.href = '{{ route("admintorneoamericanocruces") }}?torneo_id=' + torneoId;
                    }
                } else {
                    alert('Error: ' + (response.message || 'Error desconocido'));
                    btn.prop('disabled', false).text('Confirmar Cruces');
                }
            },
            error: function(xhr) {
                console.error('Error completo:', xhr);
                console.error('Response text:', xhr.responseText);
                var errorMsg = 'Error al guardar los cruces';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg += ': ' + xhr.responseJSON.message;
                }
                alert(errorMsg);
                btn.prop('disabled', false).text('Confirmar Cruces');
            }
        });
    });
});
</script>

@endsection

