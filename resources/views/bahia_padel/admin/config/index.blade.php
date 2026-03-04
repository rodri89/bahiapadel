@extends('bahia_padel/admin/plantilla')

@section('title_header','Configuración de Cruces Puntuables')

@section('contenedor')

<style>
    .form-group label,
    .form-check-label,
    h5, h6 {
        color: #000 !important;
    }
    /* Asegurar que inputs y card se vean siempre (tema claro/oscuro y rutas de CSS) */
    #form-config-cruces .form-control {
        background-color: #fff !important;
        color: #333 !important;
        border: 1px solid #ced4da !important;
        min-height: 38px;
    }
    #form-config-cruces .card {
        background-color: #fff;
    }
    #form-config-cruces .card-body {
        background-color: #fff;
    }
    #llave-8vos-content,
    #llave-4tos-content,
    #llave-semifinal-content,
    #llave-final-content,
    #llave-16avos-content {
        min-height: 40px;
        display: block;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            {{-- Listado de configuraciones existentes y botón nueva --}}
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Configuraciones de cruces</h6>
                    <a href="{{ route('adminconfig') }}?nueva=1" class="btn btn-success btn-sm">
                        <i class="fas fa-plus"></i> Nueva configuración
                    </a>
                </div>
                <div class="card-body">
                    @if($configuraciones->isEmpty())
                        <p class="text-muted mb-0">No hay configuraciones guardadas. Crea una con el botón «Nueva configuración».</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Parejas</th>
                                        <th>Rondas</th>
                                        <th class="text-right">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($configuraciones as $c)
                                    <tr>
                                        <td><strong>{{ $c->cantidad_parejas }}</strong> parejas</td>
                                        <td class="text-muted small">
                                            @if($c->tiene_16avos_final) 16avos · @endif
                                            @if($c->tiene_8vos_final) 8vos · @endif
                                            @if($c->tiene_4tos_final) 4tos @endif
                                            Semifinal · Final
                                        </td>
                                        <td class="text-right">
                                            <a href="{{ route('adminconfig') }}?editar={{ $c->id }}" class="btn btn-outline-primary btn-sm">Editar</a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        @if(isset($config) && $config !== null)
                            Editar configuración ({{ $config['cantidad_parejas'] }} parejas)
                        @else
                            Nueva configuración de cruces
                        @endif
                    </h6>
                </div>
                <div class="card-body">
                    <form id="form-config-cruces">
                        @csrf
                        @if(isset($config) && $config !== null && !empty($config['id']))
                            <input type="hidden" name="config_id" id="config_id" value="{{ $config['id'] }}">
                        @else
                            <input type="hidden" name="config_id" id="config_id" value="">
                        @endif
                        
                        <!-- Cantidad de Parejas -->
                        <div class="form-group row">
                            <label for="cantidad_parejas" class="col-sm-3 col-form-label">Cantidad de Parejas:</label>
                            <div class="col-sm-9">
                                <input type="number" class="form-control" id="cantidad_parejas" name="cantidad_parejas" min="1" value="{{ (isset($config) && $config !== null) ? ($config['cantidad_parejas'] ?? 16) : 16 }}" required>
                            </div>
                        </div>
                        
                        <!-- Rondas Eliminatorias -->
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Rondas Eliminatorias:</label>
                            <div class="col-sm-9">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="tiene_16avos" name="tiene_16avos_final" value="1" {{ (isset($config) && $config !== null && $config['tiene_16avos_final']) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="tiene_16avos">
                                        Tiene 16avos de Final
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="tiene_8vos" name="tiene_8vos_final" value="1" {{ (isset($config) && $config !== null) ? ($config['tiene_8vos_final'] ? 'checked' : '') : 'checked' }}>
                                    <label class="form-check-label" for="tiene_8vos">
                                        Tiene 8vos de Final
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="tiene_4tos" name="tiene_4tos_final" value="1" {{ (isset($config) && $config !== null) ? ($config['tiene_4tos_final'] ? 'checked' : '') : 'checked' }}>
                                    <label class="form-check-label" for="tiene_4tos">
                                        Tiene 4tos de Final
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <!-- Configuración de Llaves -->
                        <h5 class="mb-3">Configuración de Llaves</h5>
                        
                        <!-- Llave 16avos (se muestra al marcar "Tiene 16avos") -->
                        <div id="llave-16avos-container" class="mb-4" style="display: none;">
                            <h6>16avos de Final</h6>
                            <p class="text-muted small mb-2">Ej: A1, H2 (zona y posición). Solo visible si activa "Tiene 16avos de Final".</p>
                            <div id="llave-16avos-content">
                                @foreach([['A1','H2'],['B1','G2'],['C1','F2'],['D1','E2'],['E1','D2'],['F1','C2'],['G1','B2'],['H1','A2'],['A3','H4'],['B3','G4'],['C3','F4'],['D3','E4'],['E3','D4'],['F3','C4'],['G3','B4'],['H3','A4']] as $i => $par)
                                <div class="form-group row mb-2 partido-llave" data-ronda="16avos" data-partido="{{ $i+1 }}">
                                    <label class="col-sm-2 col-form-label">Partido {{ $i+1 }} (DA{{ $i+1 }}):</label>
                                    <div class="col-sm-4"><input type="text" class="form-control pareja-1-input" name="llave_16avos[{{ $i }}][pareja_1]" value="{{ $par[0] }}" placeholder="Ej: A1"></div>
                                    <div class="col-sm-1 text-center">VS</div>
                                    <div class="col-sm-4"><input type="text" class="form-control pareja-2-input" name="llave_16avos[{{ $i }}][pareja_2]" value="{{ $par[1] }}" placeholder="Ej: H2"></div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        
                        <!-- Llave 8vos -->
                        <div id="llave-8vos-container" class="mb-4">
                            <h6>8vos de Final</h6>
                            <p class="text-muted small mb-2">Ej: A1, H2 o referencias como G1-8vos (ganador partido 1 de 8vos).</p>
                            <div id="llave-8vos-content">
                                @foreach([['A1','H2'],['B1','G2'],['C1','F2'],['D1','E2'],['E1','D2'],['F1','C2'],['G1','B2'],['H1','A2']] as $i => $par)
                                <div class="form-group row mb-2 partido-llave" data-ronda="8vos" data-partido="{{ $i+1 }}">
                                    <label class="col-sm-2 col-form-label">Partido {{ $i+1 }} (O{{ $i+1 }}):</label>
                                    <div class="col-sm-4"><input type="text" class="form-control pareja-1-input" name="llave_8vos[{{ $i }}][pareja_1]" value="{{ $par[0] }}" placeholder="Ej: A1"></div>
                                    <div class="col-sm-1 text-center">VS</div>
                                    <div class="col-sm-4"><input type="text" class="form-control pareja-2-input" name="llave_8vos[{{ $i }}][pareja_2]" value="{{ $par[1] }}" placeholder="Ej: H2"></div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        
                        <!-- Llave 4tos -->
                        <div id="llave-4tos-container" class="mb-4">
                            <h6>4tos de Final</h6>
<p class="text-muted small mb-2">O1 vs O2 = ganador partido 1 octavos vs ganador partido 2 octavos. Use O1, O2, … O8.</p>
                            <div id="llave-4tos-content">
                                @foreach([['O1','O2'],['O3','O4'],['O5','O6'],['O7','O8']] as $i => $par)
                                    <div class="form-group row mb-2 partido-llave" data-ronda="4tos" data-partido="{{ $i+1 }}">
                                    <label class="col-sm-2 col-form-label">Partido {{ $i+1 }} (C{{ $i+1 }}):</label>
                                    <div class="col-sm-4"><input type="text" class="form-control pareja-1-input" name="llave_4tos[{{ $i }}][pareja_1]" value="{{ $par[0] }}" placeholder="Ej: O1"></div>
                                    <div class="col-sm-1 text-center">VS</div>
                                    <div class="col-sm-4"><input type="text" class="form-control pareja-2-input" name="llave_4tos[{{ $i }}][pareja_2]" value="{{ $par[1] }}" placeholder="Ej: O2"></div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        
                        <!-- Llave Semifinal (inputs fijos para que siempre se vean) -->
                        <div id="llave-semifinal-container" class="mb-4">
                            <h6>Semifinal</h6>
                            <p class="text-muted small mb-2">C1 vs C2 = ganador cuartos 1 vs ganador cuartos 2. Use C1, C2, C3, C4.</p>
                            <div id="llave-semifinal-content">
                                <div class="form-group row mb-2 partido-llave" data-ronda="semifinal" data-partido="1">
                                    <label class="col-sm-2 col-form-label">Partido 1 (S1):</label>
                                    <div class="col-sm-4"><input type="text" class="form-control pareja-1-input" name="llave_semifinal[0][pareja_1]" value="C1" placeholder="Ej: C1"></div>
                                    <div class="col-sm-1 text-center">VS</div>
                                    <div class="col-sm-4"><input type="text" class="form-control pareja-2-input" name="llave_semifinal[0][pareja_2]" value="C2" placeholder="Ej: C2"></div>
                                </div>
                                <div class="form-group row mb-2 partido-llave" data-ronda="semifinal" data-partido="2">
                                    <label class="col-sm-2 col-form-label">Partido 2 (S2):</label>
                                    <div class="col-sm-4"><input type="text" class="form-control pareja-1-input" name="llave_semifinal[1][pareja_1]" value="C3" placeholder="Ej: C3"></div>
                                    <div class="col-sm-1 text-center">VS</div>
                                    <div class="col-sm-4"><input type="text" class="form-control pareja-2-input" name="llave_semifinal[1][pareja_2]" value="C4" placeholder="Ej: C4"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Llave Final (input fijo para que siempre se vea) -->
                        <div id="llave-final-container" class="mb-4">
                            <h6>Final</h6>
                            <p class="text-muted small mb-2">S1 vs S2 = ganador semifinal 1 vs ganador semifinal 2.</p>
                            <div id="llave-final-content">
                                <div class="form-group row mb-2 partido-llave" data-ronda="final" data-partido="1">
                                    <label class="col-sm-2 col-form-label">Partido 1 (F1):</label>
                                    <div class="col-sm-4"><input type="text" class="form-control pareja-1-input" name="llave_final[0][pareja_1]" value="S1" placeholder="Ej: S1"></div>
                                    <div class="col-sm-1 text-center">VS</div>
                                    <div class="col-sm-4"><input type="text" class="form-control pareja-2-input" name="llave_final[0][pareja_2]" value="S2" placeholder="Ej: S2"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <div class="col-sm-12 text-right">
                                <button type="button" class="btn btn-secondary" id="btn-generar-llaves">Generar Llaves Automáticamente</button>
                                <button type="submit" class="btn btn-primary">Guardar Configuración</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Letras para las zonas (A, B, C, D, etc.)
    const letrasZonas = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P'];
    
    // Rellenar solo los valores de inputs existentes (nunca vaciar el DOM de 8vos/4tos/semi/final)
    function rellenarValoresLlave(ronda, partidos) {
        if (typeof partidos === 'string') { try { partidos = JSON.parse(partidos); } catch(e) { return; } }
        if (!Array.isArray(partidos) || partidos.length === 0) return;
        $('.partido-llave[data-ronda="'+ronda+'"]').each(function(index) {
            if (partidos[index]) {
                $(this).find('.pareja-1-input').val(partidos[index].pareja_1 || '');
                $(this).find('.pareja-2-input').val(partidos[index].pareja_2 || '');
            }
        });
    }
    
    // Cargar configuración existente: solo rellenar valores, no reemplazar DOM (así no se pierden los inputs de octavos)
    @if(isset($config) && $config !== null)
        const configExistente = @json($config);
        if (configExistente) cargarConfiguracionExistente(configExistente);
    @endif
    
    function cargarConfiguracionExistente(config) {
        var cantidadParejas = parseInt($('#cantidad_parejas').val()) || 16;
        var zonas = Math.ceil(cantidadParejas / 4);
        var letrasDisponibles = letrasZonas.slice(0, zonas);
        if (config.tiene_16avos_final) {
            $('#llave-16avos-container').show();
            if (config.llave_16avos && config.llave_16avos.length) {
                cargarLlave('16avos', config.llave_16avos);
            } else {
                generarLlave('16avos', 16, letrasDisponibles);
            }
        }
        // 8vos, 4tos, semi, final: NUNCA vaciar el contenedor; solo rellenar valores en los inputs que ya existen
        if (config.tiene_8vos_final && config.llave_8vos) rellenarValoresLlave('8vos', config.llave_8vos);
        if (config.tiene_4tos_final && config.llave_4tos) rellenarValoresLlave('4tos', config.llave_4tos);
        if (config.llave_semifinal) rellenarValoresLlave('semifinal', config.llave_semifinal);
        if (config.llave_final) rellenarValoresLlave('final', config.llave_final);
    }
    
    // Placeholders por ronda
    const placeholdersRonda = {
        '16avos': ['Ej: A1', 'Ej: H2'],
        '8vos': ['Ej: A1 o G1-8vos', 'Ej: H2 o G2-8vos'],
        '4tos': ['Ej: O1', 'Ej: O2'],
        'semifinal': ['Ej: C1', 'Ej: C2'],
        'final': ['Ej: S1', 'Ej: S2']
    };
    
    function cargarLlave(ronda, partidos) {
        if (typeof partidos === 'string') {
            try { partidos = JSON.parse(partidos); } catch (e) { return; }
        }
        if (!Array.isArray(partidos) || partidos.length === 0) return;
        const container = $('#llave-' + ronda + '-content');
        container.empty();
        const codigoRonda = obtenerCodigoRonda(ronda);
        const ph = placeholdersRonda[ronda] || ['Ej: A1', 'Ej: B2'];
        partidos.forEach(function(partido, index) {
            const partidoNum = index + 1;
            const codigoPartido = codigoRonda + partidoNum;
            const p1 = (partido && (partido.pareja_1 != null)) ? String(partido.pareja_1).replace(/</g,'&lt;') : '';
            const p2 = (partido && (partido.pareja_2 != null)) ? String(partido.pareja_2).replace(/</g,'&lt;') : '';
            const partidoHtml = '<div class="form-group row mb-2 partido-llave" data-ronda="'+ronda+'" data-partido="'+partidoNum+'">'+
                '<label class="col-sm-2 col-form-label">Partido '+partidoNum+' ('+codigoPartido+'):</label>'+
                '<div class="col-sm-4"><input type="text" class="form-control pareja-1-input" name="llave_'+ronda+'['+index+'][pareja_1]" value="'+p1+'" placeholder="'+ph[0]+'"></div>'+
                '<div class="col-sm-1 text-center">VS</div>'+
                '<div class="col-sm-4"><input type="text" class="form-control pareja-2-input" name="llave_'+ronda+'['+index+'][pareja_2]" value="'+p2+'" placeholder="'+ph[1]+'"></div></div>';
            container.append(partidoHtml);
        });
    }
    
    // Función para generar las llaves automáticamente
    $('#btn-generar-llaves').on('click', function() {
        generarLlavesAutomaticamente();
    });
    
    // Función para generar llaves automáticamente
    function generarLlavesAutomaticamente() {
        const cantidadParejas = parseInt($('#cantidad_parejas').val()) || 16;
        const tiene16avos = $('#tiene_16avos').is(':checked');
        const tiene8vos = $('#tiene_8vos').is(':checked');
        const tiene4tos = $('#tiene_4tos').is(':checked');
        
        // Calcular cantidad de zonas (asumiendo zonas de 4 parejas)
        const zonas = Math.ceil(cantidadParejas / 4);
        const letrasDisponibles = letrasZonas.slice(0, zonas);
        
        // Generar llaves según las rondas activas
        if (tiene16avos) {
            generarLlave('16avos', 16, letrasDisponibles); // 16 partidos para 16avos
        }
        if (tiene8vos) {
            generarLlave('8vos', 8, letrasDisponibles);
        }
        if (tiene4tos) {
            generarLlave('4tos', 4, letrasDisponibles);
        }
        generarLlave('semifinal', 2, letrasDisponibles);
        generarLlave('final', 1, letrasDisponibles);
    }
    
    // Función para obtener el código de ronda (DA = Dieciseis Avos)
    function obtenerCodigoRonda(ronda) {
        const codigos = {
            '16avos': 'DA',
            '8vos': 'O',
            '4tos': 'C',
            'semifinal': 'S',
            'final': 'F'
        };
        return codigos[ronda] || '';
    }
    
    // Función para generar una llave específica
    function generarLlave(ronda, cantidadPartidos, letrasDisponibles) {
        const container = $('#llave-' + ronda + '-content');
        container.empty();
        const codigoRonda = obtenerCodigoRonda(ronda);
        
        // Generar partidos
        for (let i = 0; i < cantidadPartidos; i++) {
            const partidoNum = i + 1;
            const codigoPartido = codigoRonda + partidoNum;
            let pareja1, pareja2;
            
            if (ronda === '16avos') {
                // Para 16avos: A1 vs P2, B1 vs O2, C1 vs N2, D1 vs M2, etc.
                // Usar todas las zonas disponibles, emparejando primera de una zona con segunda de la opuesta
                if (i < letrasDisponibles.length) {
                    const letra1 = letrasDisponibles[i];
                    const letra2 = letrasDisponibles[letrasDisponibles.length - 1 - i];
                    pareja1 = letra1 + '1';
                    pareja2 = letra2 + '2';
                } else {
                    // Si hay más partidos que zonas, repetir el patrón con diferentes posiciones
                    const zonaIndex = i % letrasDisponibles.length;
                    const letra1 = letrasDisponibles[zonaIndex];
                    const letra2 = letrasDisponibles[letrasDisponibles.length - 1 - zonaIndex];
                    const posicion = Math.floor(i / letrasDisponibles.length) + 1;
                    pareja1 = letra1 + posicion;
                    pareja2 = letra2 + (posicion + 1);
                }
            } else if (ronda === '8vos') {
                // Para 8vos: A1 vs H2, B1 vs G2, C1 vs F2, D1 vs E2
                const letra1 = letrasDisponibles[i];
                const letra2 = letrasDisponibles[letrasDisponibles.length - 1 - i];
                pareja1 = letra1 + '1';
                pareja2 = letra2 + '2';
            } else if (ronda === '4tos') {
                // Cuartos: O1 vs O2, O3 vs O4, O5 vs O6, O7 vs O8
                pareja1 = 'O' + (i * 2 + 1);
                pareja2 = 'O' + (i * 2 + 2);
            } else if (ronda === 'semifinal') {
                // Semifinal: C1 vs C2, C3 vs C4
                pareja1 = 'C' + (i * 2 + 1);
                pareja2 = 'C' + (i * 2 + 2);
            } else if (ronda === 'final') {
                // Final: S1 vs S2
                pareja1 = 'S1';
                pareja2 = 'S2';
            }
            
            const partidoHtml = `
                <div class="form-group row mb-2 partido-llave" data-ronda="${ronda}" data-partido="${partidoNum}">
                    <label class="col-sm-2 col-form-label">Partido ${partidoNum} (${codigoPartido}):</label>
                    <div class="col-sm-4">
                        <input type="text" class="form-control pareja-1-input" name="llave_${ronda}[${i}][pareja_1]" value="${pareja1}" placeholder="Ej: A1 o G1-8vos">
                    </div>
                    <div class="col-sm-1 text-center">VS</div>
                    <div class="col-sm-4">
                        <input type="text" class="form-control pareja-2-input" name="llave_${ronda}[${i}][pareja_2]" value="${pareja2}" placeholder="Ej: H2 o G2-8vos">
                    </div>
                </div>
            `;
            container.append(partidoHtml);
        }
    }
    
    // Mostrar/ocultar contenedores según checkboxes
    $('#tiene_16avos').on('change', function() {
        if ($(this).is(':checked')) {
            $('#llave-16avos-container').show();
            // Generar llave automáticamente si está vacía
            if ($('#llave-16avos-content').children().length === 0) {
                const cantidadParejas = parseInt($('#cantidad_parejas').val()) || 16;
                const zonas = Math.ceil(cantidadParejas / 4);
                const letrasDisponibles = letrasZonas.slice(0, zonas);
                generarLlave('16avos', 16, letrasDisponibles);
            }
        } else {
            $('#llave-16avos-container').hide();
        }
    });
    
    // Si no hay config, no vaciar 8vos/4tos/semi/final (ya vienen con HTML estático). Solo generar 16avos si el check está marcado.
    @if(!isset($config))
        if ($('#tiene_16avos').is(':checked')) {
            var zonas = Math.ceil((parseInt($('#cantidad_parejas').val()) || 16) / 4);
            generarLlave('16avos', 16, letrasZonas.slice(0, zonas));
        }
    @endif
    
    // Guardar configuración
    $('#form-config-cruces').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            config_id: $('#config_id').val() || '',
            cantidad_parejas: $('#cantidad_parejas').val(),
            tiene_16avos_final: $('#tiene_16avos').is(':checked') ? 1 : 0,
            tiene_8vos_final: $('#tiene_8vos').is(':checked') ? 1 : 0,
            tiene_4tos_final: $('#tiene_4tos').is(':checked') ? 1 : 0,
            llave_16avos: obtenerLlave('16avos'),
            llave_8vos: obtenerLlave('8vos'),
            llave_4tos: obtenerLlave('4tos'),
            llave_semifinal: obtenerLlave('semifinal'),
            llave_final: obtenerLlave('final'),
            _token: '{{ csrf_token() }}'
        };
        
        $.ajax({
            type: 'POST',
            url: '{{ route("adminconfigguardar") }}',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert('Configuración guardada correctamente');
                    window.location.reload();
                } else {
                    alert('Error al guardar: ' + (response.message || 'Error desconocido'));
                }
            },
            error: function(xhr) {
                alert('Error al guardar la configuración');
                console.error(xhr);
            }
        });
    });
    
    // Función para obtener los datos de una llave
    function obtenerLlave(ronda) {
        const partidos = [];
        $(`.partido-llave[data-ronda="${ronda}"]`).each(function() {
            const pareja1 = $(this).find('.pareja-1-input').val();
            const pareja2 = $(this).find('.pareja-2-input').val();
            if (pareja1 && pareja2) {
                partidos.push({
                    pareja_1: pareja1,
                    pareja_2: pareja2
                });
            }
        });
        return partidos.length > 0 ? JSON.stringify(partidos) : null;
    }
});
</script>

@endsection

