@extends('bahia_padel/admin/plantilla')

@section('title_header','Torneos')

@section('contenedor')

@php
    // Si $torneo es una colección, tomamos el primer elemento
    $torneo = is_iterable($torneo) && count($torneo) > 0 ? $torneo[0] : $torneo;
@endphp

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
    <div class="row justify-content-center">        
            <div class="card shadow bg-white w-100 px-5 py-3 d-flex ">
                <div id="seccion_zonas">        
                    <div class="table-responsive">
                        <table class="table table-bordered text-center w-100">
                            <thead class="thead-light">
                                <tr id="tabla-header">
                                    <th id="zona-label">Zona A</th>
                                    <th class="columna-partido" data-tipo="normal">1</th>
                                    <th class="columna-partido" data-tipo="normal">2</th>
                                    <th class="columna-partido" data-tipo="normal">3</th>
                                    <th class="columna-partido" data-tipo="final" style="display:none;">Final</th>
                                    <th class="columna-partido" data-tipo="consolacion" style="display:none;">Consolación</th>
                                </tr>
                            </thead>
                            <tbody>                                
                                <tr>
                                    <td style="width:1%; white-space:nowrap;">
                                        <div class="d-flex flex-row align-items-center justify-content-between" style="min-width:110px; max-width:280px;">
                                        
                                        <img src="{{ asset('images/jugador_img.png') }}" 
                                            class="rounded-circle img-jugador-seleccionable img-jugador-arriba" 
                                            style="width:80px; height:80px; object-fit:cover; cursor:pointer;"
                                            data-celda="celda1" data-posicion="arriba">

                                            <div class="d-flex flex-column justify-content-between align-items-center mx-2" style="height:48px;">
                                                <div class="nombre-jugador-arriba" data-celda="celda1" style="font-size:1.2rem;">Seleccionar</div>
                                                <div class="nombre-jugador-abajo" data-celda="celda1" style="font-size:1.2rem;">Seleccionar</div>
                                            </div>
                                            <img src="{{ asset('images/jugador_img.png') }}" 
                                                class="rounded-circle img-jugador-seleccionable img-jugador-abajo" 
                                                style="width:80px; height:80px; object-fit:cover; cursor:pointer;"
                                                data-celda="celda1" data-posicion="abajo">
                                        </div>
                                    </td>                         
                                    <td>
                                        <div class="seleccion-dia-horario" data-celda="1">
                                            <img src="{{ asset('bahiapadel/iconos/bahia_padel_ic.png') }}" style="width:60px; height:60px; opacity:0.3;" alt="Pareja">
                                        </div>
                                    </td>                            
                                    <td>
                                        <div class="seleccion-dia-horario" data-celda="2">
                                            <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="2">
                                                Seleccionar día/horario
                                            </button>
                                        </div>
                                    </td>                            
                                    <td>
                                        <div class="seleccion-dia-horario" data-celda="3">
                                            <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="3">
                                                Seleccionar día/horario
                                            </button>
                                        </div>
                                    </td>                            
                                </tr>
                                
                                <tr>
                                <td style="width:1%; white-space:nowrap;">
                                        <div class="d-flex flex-row align-items-center justify-content-between" style="min-width:110px; max-width:280px;">                                        
                                            <img src="{{ asset('images/jugador_img.png') }}" 
                                            class="rounded-circle img-jugador-seleccionable img-jugador-arriba" 
                                            style="width:80px; height:80px; object-fit:cover; cursor:pointer;"
                                            data-celda="celda2" data-posicion="arriba">

                                            <div class="d-flex flex-column justify-content-between align-items-center mx-2" style="height:48px;">
                                                <div class="nombre-jugador-arriba" data-celda="celda2" style="font-size:1.2rem;">Seleccionar</div>
                                                <div class="nombre-jugador-abajo" data-celda="celda2" style="font-size:1.2rem;">Seleccionar</div>
                                            </div>
                                            <img src="{{ asset('images/jugador_img.png') }}" 
                                                class="rounded-circle img-jugador-seleccionable img-jugador-abajo" 
                                                style="width:80px; height:80px; object-fit:cover; cursor:pointer;"
                                                data-celda="celda2" data-posicion="abajo">
                                        </div>
                                    </td>                        
                                    <td>
                                        <div class="seleccion-dia-horario" data-celda="4">
                                        <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="4">
                                                Seleccionar día/horario
                                            </button>
                                        </div>
                                    </td>                            
                                    <td>
                                        <div class="seleccion-dia-horario" data-celda="5">
                                            <img src="{{ asset('bahiapadel/iconos/bahia_padel_ic.png') }}" style="width:60px; height:60px; opacity:0.3;" alt="Pareja">
                                        </div>
                                    </td>                            
                                    <td>
                                        <div class="seleccion-dia-horario" data-celda="6">
                                            <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="6">
                                                Seleccionar día/horario
                                            </button>
                                        </div>
                                    </td>                              
                                </tr>

                                <tr>
                                <td style="width:1%; white-space:nowrap;">
                                        <div class="d-flex flex-row align-items-center justify-content-between" style="min-width:110px; max-width:280px;">                                        
                                        <img src="{{ asset('images/jugador_img.png') }}" 
                                            class="rounded-circle img-jugador-seleccionable img-jugador-arriba" 
                                            style="width:80px; height:80px; object-fit:cover; cursor:pointer;"
                                            data-celda="celda3" data-posicion="arriba">

                                            <div class="d-flex flex-column justify-content-between align-items-center mx-2" style="height:48px;">
                                                <div class="nombre-jugador-arriba" data-celda="celda3" style="font-size:1.2rem;">Seleccionar</div>
                                                <div class="nombre-jugador-abajo" data-celda="celda3" style="font-size:1.2rem;">Seleccionar</div>
                                            </div>
                                            <img src="{{ asset('images/jugador_img.png') }}" 
                                                class="rounded-circle img-jugador-seleccionable img-jugador-abajo" 
                                                style="width:80px; height:80px; object-fit:cover; cursor:pointer;"
                                                data-celda="celda3" data-posicion="abajo">
                                        </div>
                                    </td>                         
                                    <td>
                                        <div class="seleccion-dia-horario" data-celda="7">
                                        <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="7">
                                                Seleccionar día/horario
                                            </button>
                                        </div>
                                    </td>                            
                                    <td>
                                        <div class="seleccion-dia-horario" data-celda="8">
                                            <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="8">
                                                Seleccionar día/horario
                                            </button>
                                        </div>
                                    </td>                            
                                    <td>
                                        <div class="seleccion-dia-horario" data-celda="9">
                                            <img src="{{ asset('bahiapadel/iconos/bahia_padel_ic.png') }}" style="width:60px; height:60px; opacity:0.3;" alt="Pareja">
                                        </div>
                                    </td>
                                    <td class="columna-partido" data-tipo="final" style="display:none;">
                                        <div class="seleccion-dia-horario" data-celda="10">
                                            <img src="{{ asset('bahiapadel/iconos/bahia_padel_ic.png') }}" style="width:60px; height:60px; opacity:0.3;" alt="Pareja">
                                        </div>
                                    </td>
                                    <td class="columna-partido" data-tipo="consolacion" style="display:none;">
                                        <div class="seleccion-dia-horario" data-celda="11">
                                            <img src="{{ asset('bahiapadel/iconos/bahia_padel_ic.png') }}" style="width:60px; height:60px; opacity:0.3;" alt="Pareja">
                                        </div>
                                    </td>
                                </tr>

                                <!-- Fila para agregar cuarta pareja -->
                                <tr id="fila-agregar-pareja" style="display:none;">
                                    <td style="width:1%; white-space:nowrap;">
                                        <div class="d-flex flex-row align-items-center justify-content-between" style="min-width:110px; max-width:280px;">                                        
                                        <img src="{{ asset('images/jugador_img.png') }}" 
                                            class="rounded-circle img-jugador-seleccionable img-jugador-arriba" 
                                            style="width:80px; height:80px; object-fit:cover; cursor:pointer;"
                                            data-celda="celda4" data-posicion="arriba">

                                            <div class="d-flex flex-column justify-content-between align-items-center mx-2" style="height:48px;">
                                                <div class="nombre-jugador-arriba" data-celda="celda4" style="font-size:1.2rem;">Seleccionar</div>
                                                <div class="nombre-jugador-abajo" data-celda="celda4" style="font-size:1.2rem;">Seleccionar</div>
                                            </div>
                                            <img src="{{ asset('images/jugador_img.png') }}" 
                                                class="rounded-circle img-jugador-seleccionable img-jugador-abajo" 
                                                style="width:80px; height:80px; object-fit:cover; cursor:pointer;"
                                                data-celda="celda4" data-posicion="abajo">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="seleccion-dia-horario" data-celda="12">
                                            <img src="{{ asset('bahiapadel/iconos/bahia_padel_ic.png') }}" style="width:60px; height:60px; opacity:0.3;" alt="Pareja">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="seleccion-dia-horario" data-celda="13">
                                        <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="13">
                                                Seleccionar día/horario
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="seleccion-dia-horario" data-celda="14">
                                            <img src="{{ asset('bahiapadel/iconos/bahia_padel_ic.png') }}" style="width:60px; height:60px; opacity:0.3;" alt="Pareja">
                                        </div>
                                    </td>
                                    <td class="columna-partido" data-tipo="final" style="display:none;">
                                        <div class="seleccion-dia-horario" data-celda="10">
                                            <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="10">
                                                Seleccionar día/horario
                                            </button>
                                        </div>
                                    </td>
                                    <td class="columna-partido" data-tipo="consolacion" style="display:none;">
                                        <div class="seleccion-dia-horario" data-celda="11">
                                            <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario" data-celda="11">
                                                Seleccionar día/horario
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Botón para agregar cuarta pareja -->
                                <tr id="fila-boton-agregar">
                                    <td colspan="4" class="text-center py-3">
                                        <button type="button" class="btn btn-success btn-lg" id="btn-agregar-pareja" style="font-size:2rem; width:60px; height:60px; border-radius:50%;">
                                            +
                                        </button>
                                    </td>
                                    <td class="columna-partido" data-tipo="final" style="display:none;"></td>
                                    <td class="columna-partido" data-tipo="consolacion" style="display:none;"></td>
                                </tr>
                                
                            </tbody>
                        </table>
                    </div>

            </div>
        </div>
    </div>
    <!-- Botón Guardar, Nueva zona, Atrás y Siguiente debajo de la tabla -->
    <div class="row justify-content-center mt-4">
        <div class="col-md-8 text-center">
            <button type="button" class="btn btn-secondary btn-lg mr-2" id="btn-zona-anterior">
                Atrás
            </button>
            <button type="button" class="btn btn-secondary btn-lg mr-2" id="btn-nueva-zona">
                Nueva zona
            </button>
            <button type="button" class="btn btn-secondary btn-lg" id="btn-zona-siguiente">
                Siguiente
            </button>
            <div class="w-100 my-3"></div>
            <button type="button" class="btn btn-primary btn-lg mr-2" id="btn-guardar-torneo">
                Guardar
            </button>
            <button type="button" class="btn btn-success btn-lg" id="btn-comenzar-torneo">
                Comenzar Torneo
            </button>
        </div>
    </div>

</div>

    <!-- Acciones -->
    <div class="row justify-content-center mb-4">
        <div class="col-md-6 text-center">
            
        </div>
    </div>

    @include('bahia_padel.modal.jugadores')

<!-- Modal Seleccionar Día y Horario -->
<div class="modal fade body_admin" id="modalDiaHorario" tabindex="-1" role="dialog" aria-labelledby="modalDiaHorarioLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form id="formDiaHorario">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalDiaHorarioLabel">Seleccionar Día y Horario</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="dia">Día</label>
            <input type="date" class="form-control" id="dia" name="dia">
          </div>
          <div class="form-group">
            <label for="hora">Horario</label>
            <div class="d-flex">
                <select class="form-control mr-2" id="hora" name="hora" style="width:auto;">
                    @for ($h = 0; $h < 24; $h++)
                        <option value="{{ sprintf('%02d', $h) }}">{{ sprintf('%02d', $h) }}</option>
                    @endfor
                </select>
                <span class="align-self-center">:</span>
                <select class="form-control ml-2" id="minuto" name="minuto" style="width:auto;">
                    <option value="00">00</option>
                    <option value="15">15</option>
                    <option value="30">30</option>
                    <option value="45">45</option>
                </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
      </div>
    </form>
  </div>
</div>

    <!-- Aquí puedes incluir secciones dinámicas según la acción seleccionada -->
<script>
    function formatearRangoFechas(fechaInicio, fechaFin) {
        const meses = [
            "enero", "febrero", "marzo", "abril", "mayo", "junio",
            "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"
        ];
        const [anioI, mesI, diaI] = fechaInicio.split("-");
        const [anioF, mesF, diaF] = fechaFin.split("-");
        // Si el mes es el mismo, muestra solo una vez el mes
        if (mesI === mesF) {
            return `${parseInt(diaI)} ${meses[parseInt(mesI)-1]} - ${parseInt(diaF)} ${meses[parseInt(mesF)-1]}`;
        } else {
            return `${parseInt(diaI)} ${meses[parseInt(mesI)-1]} - ${parseInt(diaF)} ${meses[parseInt(mesF)-1]}`;
        }
    }

    function getNombreDia(fechaStr) {
        const dias = ['DOMINGO', 'LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO'];
        // Parsear manualmente la fecha para evitar problemas de zona horaria
        const [anio, mes, dia] = fechaStr.split('-').map(Number);
        const fecha = new Date(anio, mes - 1, dia); // mes - 1 porque en JS los meses van de 0 a 11
        return dias[fecha.getDay()];
    }

    // --- Mapeo de celdas vinculadas ---
    const celdasVinculadas = {
        2: 4,
        4: 2,
        3: 7,
        7: 3,
        6: 8,
        8: 6
    };
    
    // Variable para controlar si hay 4 parejas
    let tieneCuatroParejas = false;
    
    // Botón para agregar cuarta pareja
    $('#btn-agregar-pareja').on('click', function() {
        if (!tieneCuatroParejas) {
            tieneCuatroParejas = true;
            $('#fila-agregar-pareja').show();
            $('#fila-boton-agregar').hide();
            
            // Mostrar columnas de Final y Consolación
            $('.columna-partido[data-tipo="final"], .columna-partido[data-tipo="consolacion"]').show();
            
            // Actualizar encabezados de la tabla
            $('#tabla-header th[data-tipo="normal"]').eq(0).text('Semifinal 1');
            $('#tabla-header th[data-tipo="normal"]').eq(1).text('Semifinal 2');
            $('#tabla-header th[data-tipo="normal"]').eq(2).hide();
            
            // Ocultar celdas que no se usan en estructura de semifinales
            // Para 4 parejas: 
            // - Semifinal 1: Pareja 1 vs Pareja 2 (celdas 2 y 4)
            // - Semifinal 2: Pareja 3 vs Pareja 4 (celdas 7 y 13)
            // - Final: Ganador SF1 vs Ganador SF2 (celda 10)
            // - Consolación: Perdedor SF1 vs Perdedor SF2 (celda 11)
            $('.seleccion-dia-horario[data-celda="3"]').closest('td').hide();
            $('.seleccion-dia-horario[data-celda="6"]').closest('td').hide();
            $('.seleccion-dia-horario[data-celda="8"]').closest('td').hide();
            $('.seleccion-dia-horario[data-celda="9"]').closest('td').hide();
            $('.seleccion-dia-horario[data-celda="12"]').closest('td').hide();
            $('.seleccion-dia-horario[data-celda="14"]').closest('td').hide();
            
            // Mostrar celdas de Final y Consolación
            // Final está en fila 1 (Pareja 1) y fila 3 (Pareja 3) - celda 10
            // Consolación está en fila 2 (Pareja 2) y fila 4 (Pareja 4) - celda 11
        }
    });

    let celdaActual = null;

    // Al abrir el modal, guarda la celda que abrió el modal
    $(document).on('click', '.btn-abrir-modal', function() {
        celdaActual = $(this).closest('.seleccion-dia-horario');
        // Si ya hay valores, los pone en el modal
        const dia = celdaActual.data('dia') || '';
        const horario = celdaActual.data('horario') || '';
        $('#dia').val(dia);
        if(horario) {
            const [h, m] = horario.split(':');
            $('#hora').val(h);
            $('#minuto').val(m);
        } else {
            $('#hora').val('00');
            $('#minuto').val('00');
        }
    });

    // Al guardar en el modal
    $('#formDiaHorario').on('submit', function(e) {
        e.preventDefault();
        const dia = $('#dia').val();
        const hora = $('#hora').val();
        const minuto = $('#minuto').val();
        const horario = hora + ':' + minuto;
        if (celdaActual && dia && horario) {
            // Parsear fecha manualmente
            const [anio, mes, diaNum] = dia.split('-').map(Number);
            const nombreDia = getNombreDia(dia);
            const fechaFormateada = `${nombreDia}`;
            celdaActual.data('dia', dia);
            celdaActual.data('horario', horario);
            celdaActual.html(`
                <div>
                    <div style="font-size:1.3rem; font-weight:600;"> ${fechaFormateada}</div>
                    <div style="font-size:1.3rem; font-weight:600;"> ${horario}</div>
                    <button type="button" class="btn btn-sm btn-secondary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario">
                        Editar
                    </button>
                </div>
            `);

            // --- ACTUALIZA LA CELDA VINCULADA ---
            const celdaId = celdaActual.data('celda');
            const vinculadaId = celdasVinculadas[celdaId];
            if (vinculadaId) {
                const celdaVinculada = $('.seleccion-dia-horario[data-celda="' + vinculadaId + '"]');
                celdaVinculada.data('dia', dia);
                celdaVinculada.data('horario', horario);
                celdaVinculada.html(`
                    <div>
                        <div style="font-size:1.3rem; font-weight:600;"> ${fechaFormateada}</div>
                        <div style="font-size:1.3rem; font-weight:600;"> ${horario}</div>
                        <button type="button" class="btn btn-sm btn-secondary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario">
                            Editar
                        </button>
                    </div>
                `);
            }
            $('#modalDiaHorario').modal('hide');
        }
    });

    let celdaJugadorActual = null;
    let posicionJugadorActual = null;

    // Al hacer clic en la imagen de jugador
    $(document).on('click', '.img-jugador-seleccionable', function() {
        celdaJugadorActual = $(this).closest('td');
        posicionJugadorActual = $(this).data('posicion'); // 'arriba' o 'abajo'
        $('#modalSeleccionarJugador').modal('show');
    });

    // Al seleccionar un jugador en el modal
    $(document).on('click', '.jugador-option', function() {
        const nombre = $(this).data('nombre');
        const img = $(this).data('img');
        const id = $(this).data('id'); // Obtén el ID del jugador seleccionado
        if (celdaJugadorActual && posicionJugadorActual) {
            // Busca el div central de la celda y actualiza la imagen y el nombre
            if (posicionJugadorActual === 'arriba') {
                celdaJugadorActual.find('.img-jugador-arriba').attr('src', img);
                celdaJugadorActual.find('.img-jugador-arriba').attr('data-id', id); // <--- agrega esto
                celdaJugadorActual.find('.nombre-jugador-arriba').text(nombre);
            } else {
                celdaJugadorActual.find('.img-jugador-abajo').attr('src', img);
                celdaJugadorActual.find('.img-jugador-abajo').attr('data-id', id); // <--- agrega esto
                celdaJugadorActual.find('.nombre-jugador-abajo').text(nombre);
            }
        }
        $('#modalSeleccionarJugador').modal('hide');
    });

    // Cargar grupos guardados desde el backend
    let gruposGuardados = @json($grupos ?? []);
    
    // Agrupar grupos por zona
    let gruposPorZona = {};
    gruposGuardados.forEach(function(grupo) {
        if (!gruposPorZona[grupo.zona]) {
            gruposPorZona[grupo.zona] = [];
        }
        gruposPorZona[grupo.zona].push(grupo);
    });
    
    // Obtener todas las zonas únicas
    let zonasGuardadas = Object.keys(gruposPorZona).sort();
    let zonas = zonasGuardadas.length > 0 ? zonasGuardadas : ['A']; // Almacena las letras de las zonas creadas
    let datosZonas = {}; // Aquí se guardan los datos de cada zona
    let zonaIndex = 0; // Índice de la zona actual
    
    // Función para procesar grupos de una zona y preparar los datos
    function procesarGruposZona(grupos) {
        if (!grupos || grupos.length === 0) return null;
        
        // Los grupos se guardan en orden: pareja 1 (2 grupos), pareja 2 (2 grupos), pareja 3 (2 grupos)
        // Agrupar grupos por pareja (basándose en jugador_1 y jugador_2)
        let parejas = {};
        grupos.forEach(function(grupo) {
            let key = grupo.jugador_1 + '_' + grupo.jugador_2;
            if (!parejas[key]) {
                parejas[key] = [];
            }
            parejas[key].push(grupo);
        });
        
        // Convertir a array y ordenar para mantener consistencia
        let parejasArray = Object.values(parejas);
        if (parejasArray.length !== 3) return null;
        
        // Ordenar parejas por el ID del primer grupo para mantener el orden
        parejasArray.sort(function(a, b) {
            return a[0].id - b[0].id;
        });
        
        @php
            $jugadoresArray = [];
            foreach($jugadores as $j) {
                $foto = $j->foto ?? 'images/jugador_img.png';
                // Asegurar que la ruta sea correcta
                if (!str_starts_with($foto, 'http') && !str_starts_with($foto, '/')) {
                    $foto = asset($foto);
                } else if (str_starts_with($foto, 'images/')) {
                    $foto = asset($foto);
                }
                $jugadoresArray[] = [
                    'id' => $j->id,
                    'nombre' => $j->nombre ?? '',
                    'apellido' => $j->apellido ?? '',
                    'foto' => $foto
                ];
            }
        @endphp
        let jugadores = @json($jugadoresArray);
        
        let datos = {
            jugadores: {},
            horarios: {}
        };
        
        // Procesar cada pareja (en orden: pareja 1, pareja 2, pareja 3)
        parejasArray.forEach(function(parejaGrupos, index) {
            let celda = 'celda' + (index + 1);
            let primerGrupo = parejaGrupos[0];
            
            // Preparar datos de jugadores
            let jugador1 = jugadores.find(j => j.id == primerGrupo.jugador_1);
            let jugador2 = jugadores.find(j => j.id == primerGrupo.jugador_2);
            
            // Asegurar que ambos jugadores tengan sus imágenes
            let img1 = '{{ asset('images/jugador_img.png') }}';
            let img2 = '{{ asset('images/jugador_img.png') }}';
            
            if (jugador1 && jugador1.foto) {
                img1 = jugador1.foto;
                // Si la ruta no es absoluta, hacerla absoluta
                if (!img1.startsWith('http') && !img1.startsWith('/')) {
                    img1 = '/' + img1;
                }
            }
            
            if (jugador2 && jugador2.foto) {
                img2 = jugador2.foto;
                // Si la ruta no es absoluta, hacerla absoluta
                if (!img2.startsWith('http') && !img2.startsWith('/')) {
                    img2 = '/' + img2;
                }
            }
            
            datos.jugadores[celda] = {
                arriba: {
                    id: primerGrupo.jugador_1,
                    nombre: jugador1 ? (jugador1.nombre + ' ' + jugador1.apellido) : '',
                    img: img1
                },
                abajo: {
                    id: primerGrupo.jugador_2,
                    nombre: jugador2 ? (jugador2.nombre + ' ' + jugador2.apellido) : '',
                    img: img2
                }
            };
            
            // Preparar datos de horarios
            // Ordenar los grupos de la pareja por ID para mantener el orden de partidos
            parejaGrupos.sort(function(a, b) {
                return a.id - b.id;
            });
            
            if (index === 0) {
                // Pareja 1: partidos en celdas 2 y 3
                if (parejaGrupos[0]) {
                    datos.horarios[2] = { dia: parejaGrupos[0].fecha, horario: parejaGrupos[0].horario };
                }
                if (parejaGrupos[1]) {
                    datos.horarios[3] = { dia: parejaGrupos[1].fecha, horario: parejaGrupos[1].horario };
                }
            } else if (index === 1) {
                // Pareja 2: partidos en celdas 4 y 6
                if (parejaGrupos[0]) {
                    datos.horarios[4] = { dia: parejaGrupos[0].fecha, horario: parejaGrupos[0].horario };
                }
                if (parejaGrupos[1]) {
                    datos.horarios[6] = { dia: parejaGrupos[1].fecha, horario: parejaGrupos[1].horario };
                }
            } else if (index === 2) {
                // Pareja 3: partidos en celdas 7 y 8
                if (parejaGrupos[0]) {
                    datos.horarios[7] = { dia: parejaGrupos[0].fecha, horario: parejaGrupos[0].horario };
                }
                if (parejaGrupos[1]) {
                    datos.horarios[8] = { dia: parejaGrupos[1].fecha, horario: parejaGrupos[1].horario };
                }
            }
        });
        
        return datos;
    }
    
    // Inicializar datos de zonas desde grupos guardados
    zonas.forEach(function(zona) {
        if (gruposPorZona[zona]) {
            // No cargar directamente en la vista aquí, solo preparar los datos
            let datos = procesarGruposZona(gruposPorZona[zona]);
            datosZonas[zona] = datos;
        } else {
            datosZonas[zona] = null;
        }
    });

    function obtenerDatosZona() {
        function getJugadorData(celda, posicion) {
            let img = $(`.img-jugador-${posicion}[data-celda="${celda}"]`);
            let nombre = $(`.nombre-jugador-${posicion}[data-celda="${celda}"]`);
            console.log(`Buscando jugador ${celda} ${posicion}:`, {
                img: img.length,
                nombre: nombre.length,
                id: img.attr('data-id'),
                nombreText: nombre.text(),
                imgSrc: img.attr('src')
            });
            return {
                id: img.attr('data-id') || null,
                nombre: nombre.text() || null,
                img: img.attr('src') || null
            };
        }
        
        let datos = {
            jugadores: {
                celda1: {
                    arriba: getJugadorData('celda1', 'arriba'),
                    abajo: getJugadorData('celda1', 'abajo')
                },
                celda2: {
                    arriba: getJugadorData('celda2', 'arriba'),
                    abajo: getJugadorData('celda2', 'abajo')
                },
                celda3: {
                    arriba: getJugadorData('celda3', 'arriba'),
                    abajo: getJugadorData('celda3', 'abajo')
                }
            },
            horarios: {
                1: {
                    dia: $('.seleccion-dia-horario[data-celda="1"]').data('dia') || null,
                    horario: $('.seleccion-dia-horario[data-celda="1"]').data('horario') || null
                },
                2: {
                    dia: $('.seleccion-dia-horario[data-celda="2"]').data('dia') || null,
                    horario: $('.seleccion-dia-horario[data-celda="2"]').data('horario') || null
                },
                3: {
                    dia: $('.seleccion-dia-horario[data-celda="3"]').data('dia') || null,
                    horario: $('.seleccion-dia-horario[data-celda="3"]').data('horario') || null
                },
                4: {
                    dia: $('.seleccion-dia-horario[data-celda="4"]').data('dia') || null,
                    horario: $('.seleccion-dia-horario[data-celda="4"]').data('horario') || null
                },
                5: {
                    dia: $('.seleccion-dia-horario[data-celda="5"]').data('dia') || null,
                    horario: $('.seleccion-dia-horario[data-celda="5"]').data('horario') || null
                },
                6: {
                    dia: $('.seleccion-dia-horario[data-celda="6"]').data('dia') || null,
                    horario: $('.seleccion-dia-horario[data-celda="6"]').data('horario') || null
                },
                7: {
                    dia: $('.seleccion-dia-horario[data-celda="7"]').data('dia') || null,
                    horario: $('.seleccion-dia-horario[data-celda="7"]').data('horario') || null
                },
                8: {
                    dia: $('.seleccion-dia-horario[data-celda="8"]').data('dia') || null,
                    horario: $('.seleccion-dia-horario[data-celda="8"]').data('horario') || null
                },
                9: {
                    dia: $('.seleccion-dia-horario[data-celda="9"]').data('dia') || null,
                    horario: $('.seleccion-dia-horario[data-celda="9"]').data('horario') || null
                }
            },
            tieneCuatroParejas: tieneCuatroParejas
        };
        
        // Si hay 4 parejas, agregar datos de celda4 y horarios adicionales
        if (tieneCuatroParejas) {
            datos.jugadores.celda4 = {
                arriba: getJugadorData('celda4', 'arriba'),
                abajo: getJugadorData('celda4', 'abajo')
            };
            datos.horarios[10] = {
                dia: $('.seleccion-dia-horario[data-celda="10"]').data('dia') || null,
                horario: $('.seleccion-dia-horario[data-celda="10"]').data('horario') || null
            };
            datos.horarios[11] = {
                dia: $('.seleccion-dia-horario[data-celda="11"]').data('dia') || null,
                horario: $('.seleccion-dia-horario[data-celda="11"]').data('horario') || null
            };
            datos.horarios[12] = {
                dia: $('.seleccion-dia-horario[data-celda="12"]').data('dia') || null,
                horario: $('.seleccion-dia-horario[data-celda="12"]').data('horario') || null
            };
            datos.horarios[13] = {
                dia: $('.seleccion-dia-horario[data-celda="13"]').data('dia') || null,
                horario: $('.seleccion-dia-horario[data-celda="13"]').data('horario') || null
            };
            datos.horarios[14] = {
                dia: $('.seleccion-dia-horario[data-celda="14"]').data('dia') || null,
                horario: $('.seleccion-dia-horario[data-celda="14"]').data('horario') || null
            };
            datos.horarios[15] = {
                dia: $('.seleccion-dia-horario[data-celda="15"]').data('dia') || null,
                horario: $('.seleccion-dia-horario[data-celda="15"]').data('horario') || null
            };
        }
        
        return datos;
    }

    function restaurarDatosZona(datos) {
        if (!datos) {
            // Si no hay datos, limpia la tabla
            $('.img-jugador-arriba, .img-jugador-abajo').attr('src', '{{ asset('images/jugador_img.png') }}').removeAttr('data-id');
            $('.nombre-jugador-arriba, .nombre-jugador-abajo').text('Seleccionar');
            // Solo limpia las celdas que deben tener botón (2, 3, 4, 6, 7, 8)
            $('.seleccion-dia-horario[data-celda="2"], .seleccion-dia-horario[data-celda="3"], .seleccion-dia-horario[data-celda="4"], .seleccion-dia-horario[data-celda="6"], .seleccion-dia-horario[data-celda="7"], .seleccion-dia-horario[data-celda="8"]').removeData('dia').removeData('horario').html(`
                <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario">
                    Seleccionar día/horario
                </button>
            `);
            // Las celdas 1, 5, 9 muestran una imagen
            $('.seleccion-dia-horario[data-celda="1"], .seleccion-dia-horario[data-celda="5"], .seleccion-dia-horario[data-celda="9"]').removeData('dia').removeData('horario').html(`
                <img src="{{ asset('bahiapadel/iconos/bahia_padel_ic.png') }}" style="width:60px; height:60px; opacity:0.3;" alt="Pareja">
            `);
            // Ocultar cuarta pareja si estaba visible
            $('#fila-agregar-pareja').hide();
            $('#fila-boton-agregar').show();
            tieneCuatroParejas = false;
            $('.columna-partido[data-tipo="final"], .columna-partido[data-tipo="consolacion"]').hide();
            return;
        }
        
        // Verificar si hay 4 parejas
        if (datos.tieneCuatroParejas && datos.jugadores.celda4) {
            tieneCuatroParejas = true;
            $('#fila-agregar-pareja').show();
            $('#fila-boton-agregar').hide();
            $('.columna-partido[data-tipo="final"], .columna-partido[data-tipo="consolacion"]').show();
        } else {
            tieneCuatroParejas = false;
            $('#fila-agregar-pareja').hide();
            $('#fila-boton-agregar').show();
            $('.columna-partido[data-tipo="final"], .columna-partido[data-tipo="consolacion"]').hide();
        }
        
        // Jugadores
        for (let celda of ['celda1', 'celda2', 'celda3', 'celda4']) {
            if (!datos.jugadores[celda]) continue;
            
            // Restaurar jugador arriba
            let jugadorArriba = datos.jugadores[celda].arriba;
            let imgElemArriba = $(`.img-jugador-arriba[data-celda="${celda}"]`);
            let nombreElemArriba = $(`.nombre-jugador-arriba[data-celda="${celda}"]`);
            
            if (jugadorArriba && jugadorArriba.id) {
                let imgSrcArriba = jugadorArriba.img && jugadorArriba.img !== '' ? jugadorArriba.img : '{{ asset('images/jugador_img.png') }}';
                if (!imgSrcArriba.startsWith('http') && !imgSrcArriba.startsWith('/')) {
                    imgSrcArriba = '/' + imgSrcArriba;
                }
                imgElemArriba.attr('src', imgSrcArriba).attr('data-id', jugadorArriba.id);
                nombreElemArriba.text(jugadorArriba.nombre || 'Seleccionado');
            } else {
                imgElemArriba.attr('src', '{{ asset('images/jugador_img.png') }}').removeAttr('data-id');
                nombreElemArriba.text('Seleccionar');
            }
            
            // Restaurar jugador abajo
            let jugadorAbajo = datos.jugadores[celda].abajo;
            let imgElemAbajo = $(`.img-jugador-abajo[data-celda="${celda}"]`);
            let nombreElemAbajo = $(`.nombre-jugador-abajo[data-celda="${celda}"]`);
            
            if (jugadorAbajo && jugadorAbajo.id) {
                let imgSrcAbajo = jugadorAbajo.img && jugadorAbajo.img !== '' ? jugadorAbajo.img : '{{ asset('images/jugador_img.png') }}';
                if (!imgSrcAbajo.startsWith('http') && !imgSrcAbajo.startsWith('/')) {
                    imgSrcAbajo = '/' + imgSrcAbajo;
                }
                imgElemAbajo.attr('src', imgSrcAbajo).attr('data-id', jugadorAbajo.id);
                nombreElemAbajo.text(jugadorAbajo.nombre || 'Seleccionado');
            } else {
                imgElemAbajo.attr('src', '{{ asset('images/jugador_img.png') }}').removeAttr('data-id');
                nombreElemAbajo.text('Seleccionar');
            }
        }
        // Horarios
        for (let i = 1; i <= 15; i++) {
            let celda = $('.seleccion-dia-horario[data-celda="' + i + '"]');
            let dia = datos.horarios[i]?.dia;
            let horario = datos.horarios[i]?.horario;
            
            // Solo procesar celdas que deben tener horarios
            let celdasConHorario = [2, 3, 4, 6, 7, 8];
            if (tieneCuatroParejas) {
                celdasConHorario = [2, 4, 7, 10, 11, 12, 13, 14, 15];
            }
            
            if (celdasConHorario.includes(i)) {
                if (dia && horario) {
                    const [anio, mes, diaNum] = dia.split('-').map(Number);
                    const nombreDia = getNombreDia(dia);
                    const fechaFormateada = `${nombreDia}`;
                    celda.data('dia', dia);
                    celda.data('horario', horario);
                    celda.html(`
                        <div>
                            <div style="font-size:1.3rem; font-weight:600;"> ${fechaFormateada}</div>
                            <div style="font-size:1.3rem; font-weight:600;"> ${horario}</div>
                            <button type="button" class="btn btn-sm btn-secondary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario">
                                Editar
                            </button>
                        </div>
                    `);
                } else {
                    celda.removeData('dia').removeData('horario').html(`
                        <button type="button" class="btn btn-sm btn-primary mt-1 btn-abrir-modal" data-toggle="modal" data-target="#modalDiaHorario">
                            Seleccionar día/horario
                        </button>
                    `);
                }
            } else {
                // Las celdas 1, 5, 9 muestran una imagen
                celda.removeData('dia').removeData('horario').html(`
                    <img src="{{ asset('bahiapadel/iconos/bahia_padel_ic.png') }}" style="width:60px; height:60px; opacity:0.3;" alt="Pareja">
                `);
            }
        }
    }

    function actualizarZona() {
        $('#zona-label').text('Zona ' + zonas[zonaIndex]);
        restaurarDatosZona(datosZonas[zonas[zonaIndex]]);
    }
    
    // Cargar la primera zona al iniciar
    $(document).ready(function() {
        actualizarZona();
    });

    $('#btn-nueva-zona').on('click', function() {
        // Guarda los datos de la zona actual antes de cambiar
        let datosGuardados = obtenerDatosZona();
        console.log('Guardando datos de zona', zonas[zonaIndex], ':', datosGuardados);
        datosZonas[zonas[zonaIndex]] = datosGuardados;
        // Agrega una nueva zona solo si no existe ya
        let nuevaZona = String.fromCharCode(zonas[zonas.length - 1].charCodeAt(0) + 1);
        zonas.push(nuevaZona);
        datosZonas[nuevaZona] = null;
        zonaIndex = zonas.length - 1;
        actualizarZona();
    });

    $('#btn-zona-anterior').on('click', function() {
        if (zonaIndex > 0) {
            datosZonas[zonas[zonaIndex]] = obtenerDatosZona();
            zonaIndex--;
            actualizarZona();
        }
    });

    $('#btn-zona-siguiente').on('click', function() {
        if (zonaIndex < zonas.length - 1) {
            datosZonas[zonas[zonaIndex]] = obtenerDatosZona();
            zonaIndex++;
            actualizarZona();
        }
    });

    // Al guardar, también guarda los datos de la zona actual
    $('#btn-guardar-torneo').on('click', function() {
        datosZonas[zonas[zonaIndex]] = obtenerDatosZona();        
        var zona = zonas[zonaIndex];
        var torneo_id = document.getElementById("torneo_id").value;
        
        // PAREJA 1
        var pareja_1_idJugadorArriba = $('.img-jugador-arriba[data-celda="celda1"]').attr('data-id');
        var pareja_1_idJugadorAbajo = $('.img-jugador-abajo[data-celda="celda1"]').attr('data-id');
        
        // PAREJA 2
        var pareja_2_idJugadorArriba = $('.img-jugador-arriba[data-celda="celda2"]').attr('data-id');
        var pareja_2_idJugadorAbajo = $('.img-jugador-abajo[data-celda="celda2"]').attr('data-id');
        
        // PAREJA 3
        var pareja_3_idJugadorArriba = $('.img-jugador-arriba[data-celda="celda3"]').attr('data-id');
        var pareja_3_idJugadorAbajo = $('.img-jugador-abajo[data-celda="celda3"]').attr('data-id');
        
        // PAREJA 4 (si existe)
        var pareja_4_idJugadorArriba = $('.img-jugador-arriba[data-celda="celda4"]').attr('data-id');
        var pareja_4_idJugadorAbajo = $('.img-jugador-abajo[data-celda="celda4"]').attr('data-id');
        
        var pareja_1_partido_1, pareja_1_partido_1_dia, pareja_1_partido_1_horario;
        var pareja_1_partido_2, pareja_1_partido_2_dia, pareja_1_partido_2_horario;
        var pareja_2_partido_1, pareja_2_partido_1_dia, pareja_2_partido_1_horario;
        var pareja_2_partido_2, pareja_2_partido_2_dia, pareja_2_partido_2_horario;
        var pareja_3_partido_1, pareja_3_partido_1_dia, pareja_3_partido_1_horario;
        var pareja_3_partido_2, pareja_3_partido_2_dia, pareja_3_partido_2_horario;
        var pareja_4_partido_1, pareja_4_partido_1_dia, pareja_4_partido_1_horario;
        var final_dia, final_horario, consolacion_dia, consolacion_horario;
        
        if (tieneCuatroParejas) {
            // ESTRUCTURA CON 4 PAREJAS: SEMIFINALES Y FINAL
            // Semifinal 1: Pareja 1 vs Pareja 2
            pareja_1_partido_1 = $('.seleccion-dia-horario[data-celda="2"]');
            pareja_1_partido_1_dia = pareja_1_partido_1.data('dia');
            pareja_1_partido_1_horario = pareja_1_partido_1.data('horario');
            pareja_1_partido_2_dia = null;
            pareja_1_partido_2_horario = null;
            
            pareja_2_partido_1 = $('.seleccion-dia-horario[data-celda="4"]');
            pareja_2_partido_1_dia = pareja_2_partido_1.data('dia');
            pareja_2_partido_1_horario = pareja_2_partido_1.data('horario');
            pareja_2_partido_2_dia = null;
            pareja_2_partido_2_horario = null;
            
            // Semifinal 2: Pareja 3 vs Pareja 4
            pareja_3_partido_1 = $('.seleccion-dia-horario[data-celda="7"]');
            pareja_3_partido_1_dia = pareja_3_partido_1.data('dia');
            pareja_3_partido_1_horario = pareja_3_partido_1.data('horario');
            pareja_3_partido_2_dia = null;
            pareja_3_partido_2_horario = null;
            
            pareja_4_partido_1 = $('.seleccion-dia-horario[data-celda="13"]');
            pareja_4_partido_1_dia = pareja_4_partido_1.data('dia');
            pareja_4_partido_1_horario = pareja_4_partido_1.data('horario');
            
            // Final y Consolación
            final_dia = $('.seleccion-dia-horario[data-celda="10"]').data('dia');
            final_horario = $('.seleccion-dia-horario[data-celda="10"]').data('horario');
            consolacion_dia = $('.seleccion-dia-horario[data-celda="11"]').data('dia');
            consolacion_horario = $('.seleccion-dia-horario[data-celda="11"]').data('horario');
        } else {
            // ESTRUCTURA CON 3 PAREJAS: TODOS CONTRA TODOS
            pareja_1_partido_1 = $('.seleccion-dia-horario[data-celda="2"]');
            pareja_1_partido_1_dia = pareja_1_partido_1.data('dia');
            pareja_1_partido_1_horario = pareja_1_partido_1.data('horario');
            pareja_1_partido_2 = $('.seleccion-dia-horario[data-celda="3"]');
            pareja_1_partido_2_dia = pareja_1_partido_2.data('dia');
            pareja_1_partido_2_horario = pareja_1_partido_2.data('horario');
            
            pareja_2_partido_1 = $('.seleccion-dia-horario[data-celda="4"]');
            pareja_2_partido_1_dia = pareja_2_partido_1.data('dia');
            pareja_2_partido_1_horario = pareja_2_partido_1.data('horario');
            pareja_2_partido_2 = $('.seleccion-dia-horario[data-celda="6"]');
            pareja_2_partido_2_dia = pareja_2_partido_2.data('dia');
            pareja_2_partido_2_horario = pareja_2_partido_2.data('horario');
            
            pareja_3_partido_1 = $('.seleccion-dia-horario[data-celda="7"]');
            pareja_3_partido_1_dia = pareja_3_partido_1.data('dia');
            pareja_3_partido_1_horario = pareja_3_partido_1.data('horario');
            pareja_3_partido_2 = $('.seleccion-dia-horario[data-celda="8"]');
            pareja_3_partido_2_dia = pareja_3_partido_2.data('dia');
            pareja_3_partido_2_horario = pareja_3_partido_2.data('horario');
            
            pareja_4_partido_1_dia = null;
            pareja_4_partido_1_horario = null;
            final_dia = null;
            final_horario = null;
            consolacion_dia = null;
            consolacion_horario = null;
        }
                
        // Preparar datos
        var datosEnvio = {
            torneo_id: torneo_id,
            zona: zona,
            tiene_cuatro_parejas: tieneCuatroParejas ? 1 : 0,
            pareja_1_idJugadorArriba: pareja_1_idJugadorArriba,
            pareja_1_idJugadorAbajo: pareja_1_idJugadorAbajo,
            pareja_1_partido_1_dia: pareja_1_partido_1_dia,
            pareja_1_partido_1_horario: pareja_1_partido_1_horario,
            pareja_1_partido_2_dia: pareja_1_partido_2_dia,
            pareja_1_partido_2_horario: pareja_1_partido_2_horario,
            pareja_2_idJugadorArriba: pareja_2_idJugadorArriba,
            pareja_2_idJugadorAbajo: pareja_2_idJugadorAbajo,
            pareja_2_partido_1_dia: pareja_2_partido_1_dia,
            pareja_2_partido_1_horario: pareja_2_partido_1_horario,
            pareja_2_partido_2_dia: pareja_2_partido_2_dia,
            pareja_2_partido_2_horario: pareja_2_partido_2_horario,
            pareja_3_idJugadorArriba: pareja_3_idJugadorArriba,
            pareja_3_idJugadorAbajo: pareja_3_idJugadorAbajo,
            pareja_3_partido_1_dia: pareja_3_partido_1_dia,
            pareja_3_partido_1_horario: pareja_3_partido_1_horario,
            pareja_3_partido_2_dia: pareja_3_partido_2_dia,
            pareja_3_partido_2_horario: pareja_3_partido_2_horario,
            _token: '{{csrf_token()}}'
        };
        
        // Agregar datos de pareja 4 si existe
        if (tieneCuatroParejas && pareja_4_idJugadorArriba && pareja_4_idJugadorAbajo) {
            datosEnvio.pareja_4_idJugadorArriba = pareja_4_idJugadorArriba;
            datosEnvio.pareja_4_idJugadorAbajo = pareja_4_idJugadorAbajo;
            datosEnvio.pareja_4_partido_1_dia = pareja_4_partido_1_dia;
            datosEnvio.pareja_4_partido_1_horario = pareja_4_partido_1_horario;
            datosEnvio.pareja_4_partido_2_dia = pareja_4_partido_2_dia;
            datosEnvio.pareja_4_partido_2_horario = pareja_4_partido_2_horario;
            datosEnvio.final_dia = final_dia;
            datosEnvio.final_horario = final_horario;
            datosEnvio.consolacion_dia = consolacion_dia;
            datosEnvio.consolacion_horario = consolacion_horario;
        }
        
        // Enviar datos
        $.ajax({
	       type: 'POST',
	       dataType: 'JSON',
	       url: '/guardar_fecha_admin_torneo',
	       data: datosEnvio,
	       	success: function(data) {
                alert('Torneo guardado correctamente');
	        }
	    });
    });

    // Botón Comenzar Torneo
    $('#btn-comenzar-torneo').on('click', function() {
        var torneo_id = document.getElementById("torneo_id").value;
        if (!torneo_id) {
            alert('Por favor, seleccione un torneo primero');
            return;
        }
        // Redirigir a la pantalla de resultados
        window.location.href = '/admin_torneo_resultados?torneo_id=' + torneo_id;
    });
</script>
@endsection