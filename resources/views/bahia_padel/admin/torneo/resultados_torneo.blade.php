@extends('bahia_padel/admin/plantilla')

@section('title_header','Resultados del Torneo')

@section('contenedor')

<div class="container body_admin">
    <div class="row justify-content-center">
        <input hidden id="torneo_id" value="{{$torneo->id}}">            
        <div class="card shadow bg-white w-100 px-5 py-3 d-flex "
            style="border-radius: 12px; border: 1px solid #e3e6f0;">
            <div class="d-flex flex-column align-items-start flex-grow-1">
                <div class="categoria display-4 mb-2" style="font-size:2.2rem; font-weight:700; color:#4e73df;">
                    {{ $torneo->categoria ?? '-' }}º Categoría <small>- ({{ $torneo->tipo}})</small>
                </div>                    
                <div class="fechas" style="font-size:1.2rem; color:#555;">
                Fecha: {{ isset($torneo->fecha_inicio, $torneo->fecha_fin) ? (date('d', strtotime($torneo->fecha_inicio)).' '.__(strtolower(date('F', strtotime($torneo->fecha_inicio)))).' - '.date('d', strtotime($torneo->fecha_fin)).' '.__(strtolower(date('F', strtotime($torneo->fecha_fin)))) ) : '-' }}
                </div>
            </div>
            <div class="d-flex flex-column align-items-end premios" style="min-width:180px;">
                <div class="premio1" style="font-size:1.5rem; font-weight:600; color:#1a8917;">
                    1º Premio: ${{ $torneo->premio_1}}                        
                </div>
                <div class="premio2" style="font-size:1.2rem; font-weight:500; color:#555;">
                    2º Premio: ${{ $torneo->premio_2}}                        
                </div>
            </div>
        </div>
    </div>
    <br>

    @php
        $jugadoresMap = [];
        foreach($jugadores as $j) {
            $jugadoresMap[$j->id] = $j;
        }
        $zonasArray = array_keys($partidosPorZona);
        sort($zonasArray);
    @endphp

    <!-- Botones de navegación de zonas -->
    <div class="row justify-content-center mb-3">
        <div class="col-md-8 text-center">
            <button type="button" class="btn btn-secondary btn-lg mr-2" id="btn-zona-anterior-resultados">
                ← Zona Anterior
            </button>
            <span id="zona-actual-label" class="mx-3" style="font-size:1.2rem; font-weight:600; color:#4e73df;">
                Zona {{ $zonasArray[0] ?? 'A' }}
            </span>
            <button type="button" class="btn btn-secondary btn-lg ml-2" id="btn-zona-siguiente-resultados">
                Zona Siguiente →
            </button>
        </div>
    </div>

    <!-- Contenedor de zonas horizontal -->
    <div class="row justify-content-center">
        <div class="col-12">
            <div id="contenedor-zonas" style="position: relative; overflow: hidden;">
                @foreach($partidosPorZona as $zona => $partidos)
                <div class="zona-container" data-zona="{{ $zona }}" style="width: 100%; display: {{ $loop->first ? 'block' : 'none' }};">
                    <div class="card shadow bg-white px-5 py-3">
                        <h3 class="mb-4 text-center" style="color:#4e73df;">Zona {{ $zona }}</h3>
                        
                        <div class="row">
                            @foreach($partidos as $partidoId => $partidoData)
                            @php
                                $resultados = $partidoData['resultados'];
                                $pareja1 = $partidoData['pareja_1'];
                                $pareja2 = $partidoData['pareja_2'];
                                $fecha = $partidoData['fecha'] ?? null;
                                $horario = $partidoData['horario'] ?? null;
                                
                                $jugador1 = $jugadoresMap[$pareja1['jugador_1']] ?? null;
                                $jugador2 = $jugadoresMap[$pareja1['jugador_2']] ?? null;
                                $jugador3 = $pareja2 ? ($jugadoresMap[$pareja2['jugador_1']] ?? null) : null;
                                $jugador4 = $pareja2 ? ($jugadoresMap[$pareja2['jugador_2']] ?? null) : null;
                                
                                // Formatear fecha
                                $fechaFormateada = '';
                                if ($fecha && $fecha != '2000-01-01') {
                                    $diasSemana = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
                                    $timestamp = strtotime($fecha);
                                    $diaSemana = $diasSemana[date('w', $timestamp)];
                                    $diaMes = date('d', $timestamp);
                                    $fechaFormateada = ucfirst($diaSemana) . ' ' . $diaMes;
                                }
                            @endphp
                            
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border" style="height: 100%;">
                                    <div class="card-body">
                                        <h5 class="card-title text-center mb-2">Partido {{ $loop->iteration }}</h5>
                                        @if($fechaFormateada || $horario)
                                        <div class="text-center mb-3" style="font-size:0.85rem; color:#555;">
                                            @if($fechaFormateada)
                                            <div><strong>Día:</strong> {{ $fechaFormateada }}</div>
                                            @endif
                                            @if($horario && $horario != '00:00')
                                            <div><strong>Horario:</strong> {{ $horario }}</div>
                                            @endif
                                        </div>
                                        @endif
                                        
                                        <!-- Jugadores -->
                                        <div class="d-flex justify-content-around align-items-center mb-3">
                                            <!-- Pareja 1 -->
                                            <div class="text-center pareja-container pareja-1-container" data-partido-id="{{ $partidoId }}" style="position: relative; padding: 10px; border-radius: 8px; transition: all 0.3s;">
                                                @if($jugador1)
                                                <div class="mb-2">
                                                    <img src="{{ asset($jugador1->foto ?? 'images/jugador_img.png') }}" 
                                                        class="rounded-circle" 
                                                        style="width:70px; height:70px; object-fit:cover; border: 2px solid #4e73df;">
                                                    <div style="font-size:0.75rem; font-weight:600; margin-top:5px;">
                                                        {{ $jugador1->nombre ?? '' }} {{ $jugador1->apellido ?? '' }}
                                                    </div>
                                                </div>
                                                @endif
                                                @if($jugador2)
                                                <div>
                                                    <img src="{{ asset($jugador2->foto ?? 'images/jugador_img.png') }}" 
                                                        class="rounded-circle" 
                                                        style="width:70px; height:70px; object-fit:cover; border: 2px solid #4e73df;">
                                                    <div style="font-size:0.75rem; font-weight:600; margin-top:5px;">
                                                        {{ $jugador2->nombre ?? '' }} {{ $jugador2->apellido ?? '' }}
                                                    </div>
                                                </div>
                                                @endif
                                            </div>
                                            
                                            <!-- VS -->
                                            <div class="mx-3">
                                                <h4 style="color:#dc3545; font-weight:bold;">VS</h4>
                                            </div>
                                            
                                            <!-- Pareja 2 -->
                                            <div class="text-center pareja-container pareja-2-container" data-partido-id="{{ $partidoId }}" style="position: relative; padding: 10px; border-radius: 8px; transition: all 0.3s;">
                                                @if($jugador3)
                                                <div class="mb-2">
                                                    <img src="{{ asset($jugador3->foto ?? 'images/jugador_img.png') }}" 
                                                        class="rounded-circle" 
                                                        style="width:70px; height:70px; object-fit:cover; border: 2px solid #1a8917;">
                                                    <div style="font-size:0.75rem; font-weight:600; margin-top:5px;">
                                                        {{ $jugador3->nombre ?? '' }} {{ $jugador3->apellido ?? '' }}
                                                    </div>
                                                </div>
                                                @endif
                                                @if($jugador4)
                                                <div>
                                                    <img src="{{ asset($jugador4->foto ?? 'images/jugador_img.png') }}" 
                                                        class="rounded-circle" 
                                                        style="width:70px; height:70px; object-fit:cover; border: 2px solid #1a8917;">
                                                    <div style="font-size:0.75rem; font-weight:600; margin-top:5px;">
                                                        {{ $jugador4->nombre ?? '' }} {{ $jugador4->apellido ?? '' }}
                                                    </div>
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                        
                                        <!-- Resultados -->
                                        <div class="resultado-partido" data-partido-id="{{ $partidoId }}">
                                            <!-- Set 1 -->
                                            <div class="mb-2">
                                                <label style="font-size:0.8rem; font-weight:600;">Set 1</label>
                                                <div class="d-flex justify-content-center align-items-center">
                                                    <input type="number" min="0" max="99" 
                                                        class="form-control form-control-sm" 
                                                        style="width:60px;"
                                                        name="pareja_1_set_1" 
                                                        value="{{ $resultados->pareja_1_set_1 ?? 0 }}"
                                                        data-partido-id="{{ $partidoId }}">
                                                    <span class="mx-2">-</span>
                                                    <input type="number" min="0" max="99" 
                                                        class="form-control form-control-sm" 
                                                        style="width:60px;"
                                                        name="pareja_2_set_1" 
                                                        value="{{ $resultados->pareja_2_set_1 ?? 0 }}"
                                                        data-partido-id="{{ $partidoId }}">
                                                </div>
                                                <div class="d-flex justify-content-center align-items-center mt-1">
                                                    <small style="font-size:0.7rem;">TB:</small>
                                                    <input type="number" min="0" max="99" 
                                                        class="form-control form-control-sm ml-1" 
                                                        style="width:50px;"
                                                        name="pareja_1_set_1_tie_break" 
                                                        value="{{ $resultados->pareja_1_set_1_tie_break ?? 0 }}"
                                                        data-partido-id="{{ $partidoId }}">
                                                    <span class="mx-1">-</span>
                                                    <input type="number" min="0" max="99" 
                                                        class="form-control form-control-sm" 
                                                        style="width:50px;"
                                                        name="pareja_2_set_1_tie_break" 
                                                        value="{{ $resultados->pareja_2_set_1_tie_break ?? 0 }}"
                                                        data-partido-id="{{ $partidoId }}">
                                                </div>
                                            </div>
                                            
                                            <!-- Set 2 -->
                                            <div class="mb-2">
                                                <label style="font-size:0.8rem; font-weight:600;">Set 2</label>
                                                <div class="d-flex justify-content-center align-items-center">
                                                    <input type="number" min="0" max="99" 
                                                        class="form-control form-control-sm" 
                                                        style="width:60px;"
                                                        name="pareja_1_set_2" 
                                                        value="{{ $resultados->pareja_1_set_2 ?? 0 }}"
                                                        data-partido-id="{{ $partidoId }}">
                                                    <span class="mx-2">-</span>
                                                    <input type="number" min="0" max="99" 
                                                        class="form-control form-control-sm" 
                                                        style="width:60px;"
                                                        name="pareja_2_set_2" 
                                                        value="{{ $resultados->pareja_2_set_2 ?? 0 }}"
                                                        data-partido-id="{{ $partidoId }}">
                                                </div>
                                                <div class="d-flex justify-content-center align-items-center mt-1">
                                                    <small style="font-size:0.7rem;">TB:</small>
                                                    <input type="number" min="0" max="99" 
                                                        class="form-control form-control-sm ml-1" 
                                                        style="width:50px;"
                                                        name="pareja_1_set_2_tie_break" 
                                                        value="{{ $resultados->pareja_1_set_2_tie_break ?? 0 }}"
                                                        data-partido-id="{{ $partidoId }}">
                                                    <span class="mx-1">-</span>
                                                    <input type="number" min="0" max="99" 
                                                        class="form-control form-control-sm" 
                                                        style="width:50px;"
                                                        name="pareja_2_set_2_tie_break" 
                                                        value="{{ $resultados->pareja_2_set_2_tie_break ?? 0 }}"
                                                        data-partido-id="{{ $partidoId }}">
                                                </div>
                                            </div>
                                            
                                            <!-- Set 3 -->
                                            <div class="mb-2">
                                                <label style="font-size:0.8rem; font-weight:600;">Set 3</label>
                                                <div class="d-flex justify-content-center align-items-center">
                                                    <input type="number" min="0" max="99" 
                                                        class="form-control form-control-sm" 
                                                        style="width:60px;"
                                                        name="pareja_1_set_3" 
                                                        value="{{ $resultados->pareja_1_set_3 ?? 0 }}"
                                                        data-partido-id="{{ $partidoId }}">
                                                    <span class="mx-2">-</span>
                                                    <input type="number" min="0" max="99" 
                                                        class="form-control form-control-sm" 
                                                        style="width:60px;"
                                                        name="pareja_2_set_3" 
                                                        value="{{ $resultados->pareja_2_set_3 ?? 0 }}"
                                                        data-partido-id="{{ $partidoId }}">
                                                </div>
                                                <div class="d-flex justify-content-center align-items-center mt-1">
                                                    <small style="font-size:0.7rem;">TB:</small>
                                                    <input type="number" min="0" max="99" 
                                                        class="form-control form-control-sm ml-1" 
                                                        style="width:50px;"
                                                        name="pareja_1_set_3_tie_break" 
                                                        value="{{ $resultados->pareja_1_set_3_tie_break ?? 0 }}"
                                                        data-partido-id="{{ $partidoId }}">
                                                    <span class="mx-1">-</span>
                                                    <input type="number" min="0" max="99" 
                                                        class="form-control form-control-sm" 
                                                        style="width:50px;"
                                                        name="pareja_2_set_3_tie_break" 
                                                        value="{{ $resultados->pareja_2_set_3_tie_break ?? 0 }}"
                                                        data-partido-id="{{ $partidoId }}">
                                                </div>
                                            </div>
                                            
                                            <!-- Super TB -->
                                            <div class="mb-2">
                                                <label style="font-size:0.8rem; font-weight:600;">Super TB</label>
                                                <div class="d-flex justify-content-center align-items-center">
                                                    <input type="number" min="0" max="99" 
                                                        class="form-control form-control-sm" 
                                                        style="width:60px;"
                                                        name="pareja_1_set_super_tie_break" 
                                                        value="{{ $resultados->pareja_1_set_super_tie_break ?? 0 }}"
                                                        data-partido-id="{{ $partidoId }}">
                                                    <span class="mx-2">-</span>
                                                    <input type="number" min="0" max="99" 
                                                        class="form-control form-control-sm" 
                                                        style="width:60px;"
                                                        name="pareja_2_set_super_tie_break" 
                                                        value="{{ $resultados->pareja_2_set_super_tie_break ?? 0 }}"
                                                        data-partido-id="{{ $partidoId }}">
                                                </div>
                                            </div>
                                            
                                            <!-- Botón Guardar -->
                                            <div class="text-center mt-3">
                                                <button type="button" class="btn btn-sm btn-primary guardar-resultado" 
                                                    data-partido-id="{{ $partidoId }}">
                                                    Guardar Resultado
                                                </button>
                                            </div>
                                        </div>
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
    </div>

    <!-- Sección de Clasificación -->
    <div class="row justify-content-center mt-5 mb-4" id="seccion-clasificacion" style="display: none;">
        <div class="col-12">
            <div class="card shadow bg-white px-5 py-4">
                <h3 class="text-center mb-4" style="color:#4e73df;">Clasificación Zona <span id="zona-clasificacion-label"></span></h3>
                <div id="contenedor-podio" class="row justify-content-center">
                    <!-- Se llenará dinámicamente -->
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center mt-4 mb-4">
        <div class="col-md-8 text-center">
            <button type="button" class="btn btn-success btn-lg mr-3" id="btn-validar-cruces" style="display: none;">
                Validar Cruces
            </button>
            <a href="/admin_torneos" class="btn btn-secondary btn-lg">
                Volver a Torneos
            </a>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Navegación de zonas
    let zonas = @json($zonasArray);
    let zonaIndex = 0;
    
    function actualizarZona() {
        // Ocultar todas las zonas
        $('.zona-container').hide();
        // Mostrar la zona actual
        $('.zona-container[data-zona="' + zonas[zonaIndex] + '"]').show();
        // Actualizar el label
        $('#zona-actual-label').text('Zona ' + zonas[zonaIndex]);
        // Habilitar/deshabilitar botones
        $('#btn-zona-anterior-resultados').prop('disabled', zonaIndex === 0);
        $('#btn-zona-siguiente-resultados').prop('disabled', zonaIndex === zonas.length - 1);
        
        // Ocultar clasificación anterior y verificar la nueva zona
        $('#seccion-clasificacion').hide();
        $('#contenedor-podio').empty();
        
        // Verificar si la nueva zona tiene todos los partidos completos
        verificarYCalcularClasificacion();
    }
    
    $('#btn-zona-anterior-resultados').on('click', function() {
        if (zonaIndex > 0) {
            zonaIndex--;
            actualizarZona();
        }
    });
    
    $('#btn-zona-siguiente-resultados').on('click', function() {
        if (zonaIndex < zonas.length - 1) {
            zonaIndex++;
            actualizarZona();
        }
    });
    
    // Inicializar
    actualizarZona();
    
    // Verificar partidos completos al cargar la página
    verificarYCalcularClasificacion();
    
    // Verificar si todos los partidos de todas las zonas están completos
    function verificarTodosPartidosCompletos() {
        var torneoId = $('#torneo_id').val();
        var todasZonasCompletas = true;
        var zonasVerificadas = 0;
        
        zonas.forEach(function(zona) {
            $.ajax({
                type: 'POST',
                dataType: 'JSON',
                url: '{{ route("verificarpartidoscompletos") }}',
                data: {
                    torneo_id: torneoId,
                    zona: zona,
                    _token: '{{csrf_token()}}'
                },
                success: function(data) {
                    zonasVerificadas++;
                    if (!data.success || !data.todos_completos) {
                        todasZonasCompletas = false;
                    }
                    
                    // Cuando se hayan verificado todas las zonas
                    if (zonasVerificadas === zonas.length) {
                        if (todasZonasCompletas) {
                            $('#btn-validar-cruces').show();
                        } else {
                            $('#btn-validar-cruces').hide();
                        }
                    }
                },
                error: function() {
                    zonasVerificadas++;
                    todasZonasCompletas = false;
                    if (zonasVerificadas === zonas.length) {
                        $('#btn-validar-cruces').hide();
                    }
                }
            });
        });
    }
    
    // Verificar al cargar la página
    verificarTodosPartidosCompletos();
    
    // Navegar a la pantalla de cruces
    $('#btn-validar-cruces').on('click', function() {
        var torneoId = $('#torneo_id').val();
        var url = '{{ url("/admin_torneo_validar_cruces") }}?torneo_id=' + torneoId;
        window.location.href = url;
    });
    
    // Guardar resultado cuando se hace clic en el botón
    $(document).on('click', '.guardar-resultado', function() {
        var partidoId = $(this).data('partido-id');
        var resultadoPartido = $(this).closest('.resultado-partido');
        
        var datos = {
            partido_id: partidoId,
            pareja_1_set_1: resultadoPartido.find('input[name="pareja_1_set_1"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_1_set_1_tie_break: resultadoPartido.find('input[name="pareja_1_set_1_tie_break"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_2_set_1: resultadoPartido.find('input[name="pareja_2_set_1"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_2_set_1_tie_break: resultadoPartido.find('input[name="pareja_2_set_1_tie_break"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_1_set_2: resultadoPartido.find('input[name="pareja_1_set_2"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_1_set_2_tie_break: resultadoPartido.find('input[name="pareja_1_set_2_tie_break"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_2_set_2: resultadoPartido.find('input[name="pareja_2_set_2"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_2_set_2_tie_break: resultadoPartido.find('input[name="pareja_2_set_2_tie_break"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_1_set_3: resultadoPartido.find('input[name="pareja_1_set_3"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_1_set_3_tie_break: resultadoPartido.find('input[name="pareja_1_set_3_tie_break"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_2_set_3: resultadoPartido.find('input[name="pareja_2_set_3"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_2_set_3_tie_break: resultadoPartido.find('input[name="pareja_2_set_3_tie_break"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_1_set_super_tie_break: resultadoPartido.find('input[name="pareja_1_set_super_tie_break"][data-partido-id="' + partidoId + '"]').val() || 0,
            pareja_2_set_super_tie_break: resultadoPartido.find('input[name="pareja_2_set_super_tie_break"][data-partido-id="' + partidoId + '"]').val() || 0,
            _token: '{{csrf_token()}}'
        };
        
        var btn = $(this);
        btn.prop('disabled', true).text('Guardando...');
        
        $.ajax({
            type: 'POST',
            dataType: 'JSON',
            url: '{{ route("guardarresultadopartido") }}',
            data: datos,
            success: function(data) {
                if (data.success) {
                    btn.removeClass('btn-primary').addClass('btn-success').text('✓ Guardado');
                    
                    // Determinar ganador y aplicar estilo verde
                    determinarGanador(partidoId, resultadoPartido);
                    
                    // Verificar si todos los partidos están completos
                    verificarYCalcularClasificacion();
                    
                    // Verificar si todas las zonas están completas para mostrar el botón
                    setTimeout(function() {
                        verificarTodosPartidosCompletos();
                    }, 500);
                    
                    setTimeout(function() {
                        btn.removeClass('btn-success').addClass('btn-primary').text('Guardar Resultado');
                    }, 2000);
                } else {
                    alert('Error al guardar el resultado');
                    btn.prop('disabled', false).text('Guardar Resultado');
                }
            },
            error: function() {
                alert('Error al guardar el resultado');
                btn.prop('disabled', false).text('Guardar Resultado');
            }
        });
    });
    
    // Auto-guardar cuando se cambia un valor (opcional)
    $(document).on('change', '.resultados-sets input', function() {
        var partidoId = $(this).data('partido-id');
        var btn = $('.guardar-resultado[data-partido-id="' + partidoId + '"]');
        if (btn.hasClass('btn-success')) {
            btn.removeClass('btn-success').addClass('btn-primary').text('Guardar Resultado');
        }
        // Remover estilo verde cuando se cambia un valor
        $('.pareja-1-container[data-partido-id="' + partidoId + '"], .pareja-2-container[data-partido-id="' + partidoId + '"]')
            .removeClass('ganador').css('background-color', '').css('border', '');
    });
    
    // Función para determinar el ganador
    function determinarGanador(partidoId, resultadoPartido) {
        // Obtener valores de los sets
        var set1_p1 = parseInt(resultadoPartido.find('input[name="pareja_1_set_1"][data-partido-id="' + partidoId + '"]').val()) || 0;
        var set1_p2 = parseInt(resultadoPartido.find('input[name="pareja_2_set_1"][data-partido-id="' + partidoId + '"]').val()) || 0;
        var set2_p1 = parseInt(resultadoPartido.find('input[name="pareja_1_set_2"][data-partido-id="' + partidoId + '"]').val()) || 0;
        var set2_p2 = parseInt(resultadoPartido.find('input[name="pareja_2_set_2"][data-partido-id="' + partidoId + '"]').val()) || 0;
        var set3_p1 = parseInt(resultadoPartido.find('input[name="pareja_1_set_3"][data-partido-id="' + partidoId + '"]').val()) || 0;
        var set3_p2 = parseInt(resultadoPartido.find('input[name="pareja_2_set_3"][data-partido-id="' + partidoId + '"]').val()) || 0;
        var superTB_p1 = parseInt(resultadoPartido.find('input[name="pareja_1_set_super_tie_break"][data-partido-id="' + partidoId + '"]').val()) || 0;
        var superTB_p2 = parseInt(resultadoPartido.find('input[name="pareja_2_set_super_tie_break"][data-partido-id="' + partidoId + '"]').val()) || 0;
        
        // Remover estilos anteriores
        $('.pareja-1-container[data-partido-id="' + partidoId + '"], .pareja-2-container[data-partido-id="' + partidoId + '"]')
            .removeClass('ganador').css('background-color', '').css('border', '');
        
        // Si hay super tie break, ese determina el ganador
        if (superTB_p1 > 0 || superTB_p2 > 0) {
            if (superTB_p1 > superTB_p2) {
                $('.pareja-1-container[data-partido-id="' + partidoId + '"]')
                    .addClass('ganador')
                    .css('background-color', '#d4edda')
                    .css('border', '3px solid #28a745');
            } else if (superTB_p2 > superTB_p1) {
                $('.pareja-2-container[data-partido-id="' + partidoId + '"]')
                    .addClass('ganador')
                    .css('background-color', '#d4edda')
                    .css('border', '3px solid #28a745');
            }
            return;
        }
        
        // Contar sets ganados
        var setsGanadosP1 = 0;
        var setsGanadosP2 = 0;
        
        if (set1_p1 > set1_p2) setsGanadosP1++;
        else if (set1_p2 > set1_p1) setsGanadosP2++;
        
        if (set2_p1 > set2_p2) setsGanadosP1++;
        else if (set2_p2 > set2_p1) setsGanadosP2++;
        
        if (set3_p1 > set3_p2) setsGanadosP1++;
        else if (set3_p2 > set3_p1) setsGanadosP2++;
        
        // Aplicar estilo verde al ganador
        if (setsGanadosP1 > setsGanadosP2) {
            $('.pareja-1-container[data-partido-id="' + partidoId + '"]')
                .addClass('ganador')
                .css('background-color', '#d4edda')
                .css('border', '3px solid #28a745');
        } else if (setsGanadosP2 > setsGanadosP1) {
            $('.pareja-2-container[data-partido-id="' + partidoId + '"]')
                .addClass('ganador')
                .css('background-color', '#d4edda')
                .css('border', '3px solid #28a745');
        }
    }
    
    // Aplicar ganador al cargar la página si ya hay resultados
    $('.resultado-partido').each(function() {
        var partidoId = $(this).data('partido-id');
        var resultadoPartido = $(this);
        determinarGanador(partidoId, resultadoPartido);
    });
    
    // Función para verificar si todos los partidos están completos y calcular clasificación
    function verificarYCalcularClasificacion() {
        var torneoId = $('#torneo_id').val();
        var zona = zonas[zonaIndex];
        
        // Ocultar clasificación mientras se verifica
        $('#seccion-clasificacion').hide();
        $('#contenedor-podio').empty();
        
        $.ajax({
            type: 'POST',
            dataType: 'JSON',
            url: '{{ route("verificarpartidoscompletos") }}',
            data: {
                torneo_id: torneoId,
                zona: zona,
                _token: '{{csrf_token()}}'
            },
            success: function(data) {
                if (data.success && data.todos_completos) {
                    // Todos los partidos están completos, calcular clasificación
                    calcularClasificacion();
                } else {
                    // No todos los partidos están completos, ocultar clasificación
                    $('#seccion-clasificacion').hide();
                }
            },
            error: function() {
                console.log('Error al verificar partidos completos');
                $('#seccion-clasificacion').hide();
            }
        });
    }
    
    // Función para calcular clasificación
    function calcularClasificacion() {
        var torneoId = $('#torneo_id').val();
        var zona = zonas[zonaIndex];
        
        $.ajax({
            type: 'POST',
            dataType: 'JSON',
            url: '{{ route("calcularposicioneszona") }}',
            data: {
                torneo_id: torneoId,
                zona: zona,
                _token: '{{csrf_token()}}'
            },
            success: function(data) {
                if (data.success && data.posiciones) {
                    mostrarPodio(data.posiciones, zona);
                }
            },
            error: function() {
                console.log('Error al calcular las posiciones');
            }
        });
    }
    
    // Calcular posiciones manualmente (botón opcional, puede ocultarse)
    $('#btn-calcular-posiciones').on('click', function() {
        calcularClasificacion();
    });
    
    // Función para mostrar el podio
    function mostrarPodio(posiciones, zona) {
        @php
            $jugadoresArray = [];
            foreach($jugadores as $j) {
                $jugadoresArray[] = [
                    'id' => $j->id,
                    'nombre' => $j->nombre ?? '',
                    'apellido' => $j->apellido ?? '',
                    'foto' => $j->foto ?? asset('images/jugador_img.png')
                ];
            }
        @endphp
        var jugadores = @json($jugadoresArray);
        
        var contenedor = $('#contenedor-podio');
        contenedor.empty();
        
        $('#zona-clasificacion-label').text(zona);
        $('#seccion-clasificacion').show();
        
        // Mostrar las 3 primeras posiciones
        var podios = [
            { pos: 1, clase: 'gold', icono: '🥇', titulo: '1º Lugar' },
            { pos: 2, clase: 'silver', icono: '🥈', titulo: '2º Lugar' },
            { pos: 3, clase: 'bronze', icono: '🥉', titulo: '3º Lugar' }
        ];
        
        // Mostrar podio de izquierda a derecha: 1º, 2º, 3º
        podios.forEach(function(podio) {
            if (posiciones[podio.pos - 1]) {
                var pareja = posiciones[podio.pos - 1];
                var jugador1 = jugadores.find(j => j.id == pareja.jugador_1);
                var jugador2 = jugadores.find(j => j.id == pareja.jugador_2);
                
                var html = `
                    <div class="col-md-4">
                        <div class="card text-center border-${podio.clase}" style="border-width: 3px !important; height: 100%;">
                            <div class="card-body">
                                <h4 class="mb-3">${podio.icono} ${podio.titulo}</h4>
                                <div class="d-flex justify-content-center align-items-center mb-2">
                                    ${jugador1 ? `
                                    <div class="text-center mx-2">
                                        <img src="${jugador1.foto}" class="rounded-circle" style="width:80px; height:80px; object-fit:cover; border: 3px solid #${podio.clase === 'gold' ? 'FFD700' : podio.clase === 'silver' ? 'C0C0C0' : 'CD7F32'};">
                                        <div style="font-size:0.9rem; font-weight:600; margin-top:5px;">
                                            ${jugador1.nombre} ${jugador1.apellido}
                                        </div>
                                    </div>
                                    ` : ''}
                                    ${jugador2 ? `
                                    <div class="text-center mx-2">
                                        <img src="${jugador2.foto}" class="rounded-circle" style="width:80px; height:80px; object-fit:cover; border: 3px solid #${podio.clase === 'gold' ? 'FFD700' : podio.clase === 'silver' ? 'C0C0C0' : 'CD7F32'};">
                                        <div style="font-size:0.9rem; font-weight:600; margin-top:5px;">
                                            ${jugador2.nombre} ${jugador2.apellido}
                                        </div>
                                    </div>
                                    ` : ''}
                                </div>
                                <div style="font-size:0.85rem; color:#555;">
                                    <div>Puntos: ${pareja.puntos || 0}</div>
                                    <div>Partidos: ${pareja.partidos_ganados || 0} - ${pareja.partidos_perdidos || 0}</div>
                                    <div>Sets: ${pareja.sets_ganados || 0} - ${pareja.sets_perdidos || 0}</div>
                                    <div>Juegos: ${pareja.juegos_ganados || 0} - ${pareja.juegos_perdidos || 0}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                contenedor.append(html);
            }
        });
        
        // Scroll suave hacia la clasificación
        $('html, body').animate({
            scrollTop: $('#seccion-clasificacion').offset().top - 100
        }, 500);
    }
});
</script>

@endsection

