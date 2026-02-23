@extends('bahia_padel/admin/plantilla')

@section('title_header','Configuración de Cruces Puntuables')

@section('contenedor')

<style>
    .form-group label,
    .form-check-label,
    h5, h6 {
        color: #000 !important;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Configuración de Cruces para Torneos Puntuables</h6>
                </div>
                <div class="card-body">
                    <form id="form-config-cruces">
                        @csrf
                        
                        <!-- Cantidad de Parejas -->
                        <div class="form-group row">
                            <label for="cantidad_parejas" class="col-sm-3 col-form-label">Cantidad de Parejas:</label>
                            <div class="col-sm-9">
                                <input type="number" class="form-control" id="cantidad_parejas" name="cantidad_parejas" min="1" value="{{ $config['cantidad_parejas'] ?? 16 }}" required>
                            </div>
                        </div>
                        
                        <!-- Rondas Eliminatorias -->
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Rondas Eliminatorias:</label>
                            <div class="col-sm-9">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="tiene_16avos" name="tiene_16avos_final" value="1" {{ isset($config) && $config['tiene_16avos_final'] ? 'checked' : '' }}>
                                    <label class="form-check-label" for="tiene_16avos">
                                        Tiene 16avos de Final
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="tiene_8vos" name="tiene_8vos_final" value="1" {{ isset($config) && $config['tiene_8vos_final'] ? 'checked' : (!isset($config) ? 'checked' : '') }}>
                                    <label class="form-check-label" for="tiene_8vos">
                                        Tiene 8vos de Final
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="tiene_4tos" name="tiene_4tos_final" value="1" {{ isset($config) && $config['tiene_4tos_final'] ? 'checked' : (!isset($config) ? 'checked' : '') }}>
                                    <label class="form-check-label" for="tiene_4tos">
                                        Tiene 4tos de Final
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <!-- Configuración de Llaves -->
                        <h5 class="mb-3">Configuración de Llaves</h5>
                        
                        <!-- Llave 16avos -->
                        <div id="llave-16avos-container" class="mb-4" style="display: none;">
                            <h6>16avos de Final</h6>
                            <div id="llave-16avos-content"></div>
                        </div>
                        
                        <!-- Llave 8vos -->
                        <div id="llave-8vos-container" class="mb-4">
                            <h6>8vos de Final</h6>
                            <div id="llave-8vos-content"></div>
                        </div>
                        
                        <!-- Llave 4tos -->
                        <div id="llave-4tos-container" class="mb-4">
                            <h6>4tos de Final</h6>
                            <div id="llave-4tos-content"></div>
                        </div>
                        
                        <!-- Llave Semifinal -->
                        <div id="llave-semifinal-container" class="mb-4">
                            <h6>Semifinal</h6>
                            <p class="text-muted small mb-2">Use <strong>G1-4tos</strong>, <strong>G2-4tos</strong>, <strong>G3-4tos</strong>, <strong>G4-4tos</strong> = Ganadores de Cuartos 1, 2, 3 y 4 (no confundir con zonas A,B,C,D).</p>
                            <div id="llave-semifinal-content"></div>
                        </div>
                        
                        <!-- Llave Final -->
                        <div id="llave-final-container" class="mb-4">
                            <h6>Final</h6>
                            <div id="llave-final-content"></div>
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
    
    // Cargar configuración existente si hay
    @if(isset($config))
        const configExistente = @json($config);
        cargarConfiguracionExistente(configExistente);
    @endif
    
    // Función para cargar configuración existente
    function cargarConfiguracionExistente(config) {
        // Mostrar/ocultar contenedores según configuración
        if (config.tiene_16avos_final) {
            $('#llave-16avos-container').show();
            if (config.llave_16avos) {
                cargarLlave('16avos', config.llave_16avos);
            }
        }
        if (config.tiene_8vos_final) {
            if (config.llave_8vos) {
                cargarLlave('8vos', config.llave_8vos);
            }
        }
        if (config.tiene_4tos_final) {
            if (config.llave_4tos) {
                cargarLlave('4tos', config.llave_4tos);
            }
        }
        if (config.llave_semifinal) {
            cargarLlave('semifinal', config.llave_semifinal);
        }
        if (config.llave_final) {
            cargarLlave('final', config.llave_final);
        }
    }
    
    // Placeholders por ronda
    const placeholdersRonda = {
        '16avos': ['Ej: A1', 'Ej: H2'],
        '8vos': ['Ej: A1 o G1-8vos', 'Ej: H2 o G2-8vos'],
        '4tos': ['Ej: G1-8vos', 'Ej: G2-8vos'],
        'semifinal': ['Ej: G1-4tos (ganador Cuartos 1)', 'Ej: G2-4tos (ganador Cuartos 2)'],
        'final': ['Ej: G1-semifinal', 'Ej: G2-semifinal']
    };
    
    function cargarLlave(ronda, partidos) {
        const container = $('#llave-' + ronda + '-content');
        container.empty();
        const codigoRonda = obtenerCodigoRonda(ronda);
        const ph = placeholdersRonda[ronda] || ['Ej: A1', 'Ej: B2'];
        
        partidos.forEach(function(partido, index) {
            const partidoNum = index + 1;
            const codigoPartido = codigoRonda + partidoNum;
            const partidoHtml = `
                <div class="form-group row mb-2 partido-llave" data-ronda="${ronda}" data-partido="${partidoNum}">
                    <label class="col-sm-2 col-form-label">Partido ${partidoNum} (${codigoPartido}):</label>
                    <div class="col-sm-5">
                        <input type="text" class="form-control pareja-1-input" name="llave_${ronda}[${index}][pareja_1]" value="${partido.pareja_1 || ''}" placeholder="${ph[0]}">
                    </div>
                    <div class="col-sm-1 text-center">VS</div>
                    <div class="col-sm-4">
                        <input type="text" class="form-control pareja-2-input" name="llave_${ronda}[${index}][pareja_2]" value="${partido.pareja_2 || ''}" placeholder="${ph[1]}">
                    </div>
                </div>
            `;
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
    
    // Función para obtener el código de ronda
    function obtenerCodigoRonda(ronda) {
        const codigos = {
            '16avos': '16',
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
                // Para 4tos: usar referencias a ganadores de octavos (G1-8vos, G2-8vos, etc.)
                pareja1 = 'G' + (i * 2 + 1) + '-8vos';
                pareja2 = 'G' + (i * 2 + 2) + '-8vos';
            } else if (ronda === 'semifinal') {
                // Para semifinal: usar referencias a ganadores de cuartos (G1-4tos, G2-4tos, etc.)
                pareja1 = 'G' + (i * 2 + 1) + '-4tos';
                pareja2 = 'G' + (i * 2 + 2) + '-4tos';
            } else if (ronda === 'final') {
                // Para final: usar referencias a ganadores de semifinal (G1-semifinal, G2-semifinal)
                pareja1 = 'G1-semifinal';
                pareja2 = 'G2-semifinal';
            }
            
            const partidoHtml = `
                <div class="form-group row mb-2 partido-llave" data-ronda="${ronda}" data-partido="${partidoNum}">
                    <label class="col-sm-2 col-form-label">Partido ${partidoNum} (${codigoPartido}):</label>
                    <div class="col-sm-5">
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
    
    // Generar llaves iniciales si no hay configuración existente
    @if(!isset($config))
        generarLlavesAutomaticamente();
    @endif
    
    // Guardar configuración
    $('#form-config-cruces').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
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

