@extends('bahia_padel.home.plantilla')

@section('title_header', 'Inscripción - Calendario - Bahía Pádel')

@section('contenedor')
@php
    $tituloTorneo = $evento->nombre ?: ($evento->categoria.'ª categoría · '.$evento->tipo_label);
    $txtFechas = $evento->textoFechasTorneo();
    $valorInscr = ($evento->valor_inscripcion !== null && $evento->valor_inscripcion !== '')
        ? '$'.number_format((float) $evento->valor_inscripcion, 0, ',', '.')
        : null;
@endphp
<style>
  .inscribir-resumen {
    border: 1px solid rgba(148, 163, 184, 0.35);
    border-radius: 12px;
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
    background: rgba(255, 255, 255, 0.04);
  }
  body.dark-mode .inscribir-resumen {
    background: rgba(45, 45, 45, 0.5);
    border-color: rgba(148, 163, 184, 0.25);
  }
  .inscribir-resumen h2 {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
  }
  .inscribir-form label {
    font-weight: 500;
    font-size: 0.9rem;
  }
  .inscribir-form .form-section-title {
    font-size: 1rem;
    font-weight: 600;
    margin-top: 1.25rem;
    margin-bottom: 0.75rem;
    padding-bottom: 0.35rem;
    border-bottom: 1px solid rgba(148, 163, 184, 0.35);
  }
  .jugador-buscador {
    position: relative;
  }
  .jugador-buscador-lista {
    position: absolute;
    left: 0;
    right: 0;
    top: calc(100% + 6px);
    z-index: 20;
    background: #fff;
    border: 1px solid rgba(148, 163, 184, 0.45);
    border-radius: 10px;
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
    max-height: 260px;
    overflow: auto;
    display: none;
  }
  body.dark-mode .jugador-buscador-lista {
    background: #1f2937;
    border-color: rgba(148, 163, 184, 0.25);
  }
  .jugador-buscador-item {
    padding: 0.6rem 0.85rem;
    cursor: pointer;
    border-bottom: 1px solid rgba(148, 163, 184, 0.2);
  }
  .jugador-buscador-item:last-child {
    border-bottom: none;
  }
  .jugador-buscador-item:hover {
    background: rgba(255, 2, 100, 0.08);
  }
  body.dark-mode .jugador-buscador-item:hover {
    background: rgba(255, 2, 100, 0.18);
  }
  .jugador-seleccion {
    border: 1px solid rgba(148, 163, 184, 0.35);
    border-radius: 10px;
    padding: 0.75rem 0.9rem;
    background: rgba(255, 255, 255, 0.04);
  }
  body.dark-mode .jugador-seleccion {
    background: rgba(45, 45, 45, 0.5);
    border-color: rgba(148, 163, 184, 0.25);
  }
</style>

<section class="page-header-img mb-4">
    <div class="page-header-img-inner">
        <img src="{{ asset('images/home/reglamento.webp') }}" alt="Inscripción" class="img-fluid w-100">
        <div class="page-header-img-overlay"></div>
        <h1 class="page-header-title">Inscripción</h1>
    </div>
</section>

<section class="py-3 page-content-home">
    <div class="inscribir-resumen">
        <h2 class="mb-1">{{ $tituloTorneo }}</h2>
        @if($evento->nombre)
            <p class="text-secondary small mb-2">{{ $evento->categoria }}ª · {{ $evento->tipo_label }}</p>
        @endif
        @if($txtFechas !== '')
            <p class="mb-0"><strong>Fecha:</strong> {{ $txtFechas }}</p>
        @endif
        @if($valorInscr)
            <p class="mb-0 mt-2"><strong>Valor inscripción por jugador:</strong> {{ $valorInscr }}</p>
        @endif
    </div>

    <form method="post" action="{{ route('home.calendario.inscribir.guardar', $evento) }}" class="inscribir-form">
        @csrf

        <div class="form-section-title">Jugador 1</div>
        <input type="hidden" id="jugador1_nombre" name="jugador1_nombre" value="{{ old('jugador1_nombre') }}">
        <input type="hidden" id="jugador1_apellido" name="jugador1_apellido" value="{{ old('jugador1_apellido') }}">
        <input type="hidden" id="jugador1_telefono" name="jugador1_telefono" value="{{ old('jugador1_telefono') }}">
        <div class="form-group jugador-buscador">
            <label for="jugador1_buscar">Buscar jugador</label>
            <input type="text" class="form-control" id="jugador1_buscar" placeholder="Escribí nombre o apellido…" autocomplete="off">
            <div class="jugador-buscador-lista" id="jugador1_lista"></div>
            @if($errors->has('jugador1_nombre') || $errors->has('jugador1_apellido') || $errors->has('jugador1_telefono'))
                <div class="text-danger small mt-2">Completá el Jugador 1.</div>
            @endif
        </div>
        <div class="jugador-seleccion mb-3" id="jugador1_seleccion" style="display:none;"></div>
        <button type="button" class="btn btn-outline-secondary btn-sm mb-2" id="jugador1_btn_nuevo">Crear nuevo jugador</button>
        <div id="jugador1_form_nuevo" style="display:none;">
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="jugador1_nuevo_nombre">Nombre</label>
                    <input type="text" class="form-control" id="jugador1_nuevo_nombre" maxlength="120">
                </div>
                <div class="form-group col-md-4">
                    <label for="jugador1_nuevo_apellido">Apellido</label>
                    <input type="text" class="form-control" id="jugador1_nuevo_apellido" maxlength="120">
                </div>
                <div class="form-group col-md-4">
                    <label for="jugador1_nuevo_tel">Teléfono <span class="text-muted font-weight-normal">(opcional)</span></label>
                    <input type="text" class="form-control" id="jugador1_nuevo_tel" maxlength="40" inputmode="tel">
                </div>
            </div>
            <button type="button" class="btn btn-primary btn-sm" id="jugador1_crear">Crear y seleccionar</button>
            <button type="button" class="btn btn-link btn-sm text-secondary" id="jugador1_cancelar">Cancelar</button>
        </div>

        <div class="form-section-title">Jugador 2</div>
        <input type="hidden" id="jugador2_nombre" name="jugador2_nombre" value="{{ old('jugador2_nombre') }}">
        <input type="hidden" id="jugador2_apellido" name="jugador2_apellido" value="{{ old('jugador2_apellido') }}">
        <input type="hidden" id="jugador2_telefono" name="jugador2_telefono" value="{{ old('jugador2_telefono') }}">
        <div class="form-group jugador-buscador">
            <label for="jugador2_buscar">Buscar jugador</label>
            <input type="text" class="form-control" id="jugador2_buscar" placeholder="Escribí nombre o apellido…" autocomplete="off">
            <div class="jugador-buscador-lista" id="jugador2_lista"></div>
            @if($errors->has('jugador2_nombre') || $errors->has('jugador2_apellido'))
                <div class="text-danger small mt-2">Completá el Jugador 2.</div>
            @endif
        </div>
        <div class="jugador-seleccion mb-3" id="jugador2_seleccion" style="display:none;"></div>
        <button type="button" class="btn btn-outline-secondary btn-sm mb-2" id="jugador2_btn_nuevo">Crear nuevo jugador</button>
        <div id="jugador2_form_nuevo" style="display:none;">
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="jugador2_nuevo_nombre">Nombre</label>
                    <input type="text" class="form-control" id="jugador2_nuevo_nombre" maxlength="120">
                </div>
                <div class="form-group col-md-4">
                    <label for="jugador2_nuevo_apellido">Apellido</label>
                    <input type="text" class="form-control" id="jugador2_nuevo_apellido" maxlength="120">
                </div>
                <div class="form-group col-md-4">
                    <label for="jugador2_nuevo_tel">Teléfono <span class="text-muted font-weight-normal">(opcional)</span></label>
                    <input type="text" class="form-control" id="jugador2_nuevo_tel" maxlength="40" inputmode="tel">
                </div>
            </div>
            <button type="button" class="btn btn-primary btn-sm" id="jugador2_crear">Crear y seleccionar</button>
            <button type="button" class="btn btn-link btn-sm text-secondary" id="jugador2_cancelar">Cancelar</button>
        </div>

        <div class="form-group">
            <label for="disponibilidad_horaria">Disponibilidad horaria</label>
            <textarea class="form-control @error('disponibilidad_horaria') is-invalid @enderror" id="disponibilidad_horaria" name="disponibilidad_horaria" rows="4" required maxlength="5000" placeholder="Ej.: Sábados por la tarde, entre semana después de las 19 hs…">{{ old('disponibilidad_horaria') }}</textarea>
            @error('disponibilidad_horaria')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <button type="submit" class="btn btn-primary px-4">Guardar</button>
        <a href="{{ route('home.calendario') }}" class="btn btn-link text-secondary">Volver al calendario</a>
    </form>
</section>

<script>
(function() {
    var buscarUrl = '{{ route('buscar.jugadores.publico') }}';
    var crearUrl = '{{ route('home.calendario.crear.jugador') }}';
    var csrf = '{{ csrf_token() }}';

    function escHtml(s) {
        if (s === null || s === undefined) return '';
        return $('<div/>').text(String(s)).html();
    }

    function setJugador(prefix, j) {
        $('#' + prefix + '_nombre').val(j.nombre || '');
        $('#' + prefix + '_apellido').val(j.apellido || '');
        $('#' + prefix + '_telefono').val(j.telefono || '');
        $('#' + prefix + '_seleccion').html(
            '<div><strong>Seleccionado:</strong> ' + escHtml((j.nombre || '') + ' ' + (j.apellido || '')) + '</div>' +
            '<div class="small text-muted">Tel: ' + escHtml(j.telefono || '—') + '</div>' +
            '<button type="button" class="btn btn-link btn-sm px-0 mt-2 text-secondary" id="' + prefix + '_limpiar\">Cambiar</button>'
        ).show();
        $('#' + prefix + '_buscar').val('').prop('disabled', true);
        $('#' + prefix + '_lista').hide().empty();
        $('#' + prefix + '_form_nuevo').hide();

        $('#' + prefix + '_limpiar').on('click', function() {
            $('#' + prefix + '_nombre').val('');
            $('#' + prefix + '_apellido').val('');
            $('#' + prefix + '_telefono').val('');
            $('#' + prefix + '_seleccion').hide().empty();
            $('#' + prefix + '_buscar').prop('disabled', false).focus();
        });
    }

    function renderLista(prefix, jugadores) {
        var $lista = $('#' + prefix + '_lista');
        if (!jugadores || jugadores.length === 0) {
            $lista.html('<div class="jugador-buscador-item text-muted">Sin resultados</div>').show();
            return;
        }
        var html = jugadores.map(function(j) {
            var label = (j.nombre || '') + ' ' + (j.apellido || '');
            var tel = j.telefono ? (' · ' + j.telefono) : '';
            return '<div class="jugador-buscador-item" data-id="' + escHtml(j.id) + '" data-nombre="' + escHtml(j.nombre) + '" data-apellido="' + escHtml(j.apellido) + '" data-telefono="' + escHtml(j.telefono || '') + '">' +
                '<strong>' + escHtml(label) + '</strong><span class="text-muted small">' + escHtml(tel) + '</span>' +
            '</div>';
        }).join('');
        $lista.html(html).show();
        $lista.find('.jugador-buscador-item').on('click', function() {
            var $it = $(this);
            setJugador(prefix, {
                id: $it.data('id'),
                nombre: $it.data('nombre'),
                apellido: $it.data('apellido'),
                telefono: $it.data('telefono')
            });
        });
    }

    function bindBuscador(prefix) {
        var t = null;
        $('#' + prefix + '_buscar').on('input', function() {
            var q = $(this).val().trim();
            clearTimeout(t);
            if (q.length < 2) {
                $('#' + prefix + '_lista').hide().empty();
                return;
            }
            t = setTimeout(function() {
                $.post(buscarUrl, { _token: csrf, busqueda: q })
                    .done(function(r) {
                        renderLista(prefix, (r && r.jugadores) ? r.jugadores.slice(0, 15) : []);
                    })
                    .fail(function() {
                        $('#' + prefix + '_lista').html('<div class="jugador-buscador-item text-danger">Error al buscar</div>').show();
                    });
            }, 250);
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('#' + prefix + '_buscar').length && !$(e.target).closest('#' + prefix + '_lista').length) {
                $('#' + prefix + '_lista').hide();
            }
        });

        $('#' + prefix + '_btn_nuevo').on('click', function() {
            $('#' + prefix + '_form_nuevo').toggle();
        });
        $('#' + prefix + '_cancelar').on('click', function() {
            $('#' + prefix + '_form_nuevo').hide();
        });
        $('#' + prefix + '_crear').on('click', function() {
            var nombre = $('#' + prefix + '_nuevo_nombre').val().trim();
            var apellido = $('#' + prefix + '_nuevo_apellido').val().trim();
            var telefono = $('#' + prefix + '_nuevo_tel').val().trim();
            if (!nombre || !apellido) {
                alert('Completá nombre y apellido.');
                return;
            }
            var $btn = $(this);
            $btn.prop('disabled', true);
            $.post(crearUrl, { _token: csrf, nombre: nombre, apellido: apellido, telefono: telefono })
                .done(function(r) {
                    if (!r || !r.success || !r.jugador) {
                        alert('No se pudo crear el jugador.');
                        return;
                    }
                    setJugador(prefix, r.jugador);
                })
                .fail(function(xhr) {
                    var msg = 'No se pudo crear el jugador.';
                    if (xhr && xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    alert(msg);
                })
                .always(function() { $btn.prop('disabled', false); });
        });
    }

    bindBuscador('jugador1');
    bindBuscador('jugador2');
})();
</script>
@endsection
