@extends('bahia_padel/admin/plantilla')

@section('title_header','Ranking por categoría')

@section('contenedor')

<style>
    .ranking-table th, .ranking-table td { color: #000 !important; }
    .ranking-table thead th { font-weight: 600; border-bottom: 2px solid #4e73df; }
    .ranking-foto { width: 40px; height: 40px; object-fit: cover; border-radius: 50%; }
    #modalReferenciasPuntuacion .table th,
    #modalReferenciasPuntuacion .table td,
    #modalReferenciasPuntuacion .form-control { color: #000 !important; background-color: #fff !important; }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Ranking por categoría</h6>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-referencias-puntuacion" title="Ver y editar puntos por posición">
                        <i class="fas fa-list-ol"></i> Referencias de puntuación
                    </button>
                </div>
                <div class="card-body">
                    <form method="get" action="{{ route('adminranking') }}" class="form-inline mb-4 flex-wrap gap-2">
                        <label class="mr-2 mb-1 mb-md-0" for="tipo">Tipo:</label>
                        <select name="tipo" id="tipo" class="form-control form-control-sm mr-3 mb-1 mb-md-0" style="min-width: 120px;">
                            @foreach($tipos as $valor => $etiqueta)
                                <option value="{{ $valor }}" {{ $valor === $tipo_seleccionado ? 'selected' : '' }}>{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                        @if(!$categorias->isEmpty())
                        <label class="mr-2 mb-1 mb-md-0" for="categoria">Categoría:</label>
                        <select name="categoria" id="categoria" class="form-control form-control-sm mr-3 mb-1 mb-md-0" style="min-width: 120px;">
                            @foreach($categorias as $cat)
                                <option value="{{ $cat }}" {{ (int)$cat === (int)$categoria_seleccionada ? 'selected' : '' }}>{{ $cat }}º Categoría</option>
                            @endforeach
                        </select>
                        @endif
                        @if(!$temporadas->isEmpty())
                        <label class="mr-2 mb-1 mb-md-0" for="temporada">Temporada:</label>
                        <select name="temporada" id="temporada" class="form-control form-control-sm mr-2 mb-1 mb-md-0" style="min-width: 100px;">
                            @foreach($temporadas as $temp)
                                <option value="{{ $temp }}" {{ (int)$temp === (int)$temporada_seleccionada ? 'selected' : '' }}>{{ $temp }}</option>
                            @endforeach
                        </select>
                        @endif
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Ver</button>
                    </form>
                    @if($categorias->isEmpty() && $temporadas->isEmpty())
                        <p class="text-muted mb-0">No hay datos de ranking para el tipo {{ $tipos[$tipo_seleccionado] ?? $tipo_seleccionado }}. Los puntos se cargan al asignar puntos en los torneos puntuables (cruces eliminatorios).</p>
                    @endif

                    @if(!$ranking->isEmpty())
                        <div class="table-responsive">
                            <table class="table table-sm table-hover ranking-table mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 60px;">Pos.</th>
                                        <th style="min-width: 180px;">Jugador</th>
                                        @foreach($torneos_ranking as $t)
                                            <th class="text-center" style="min-width: 90px;" title="{{ $t->nombre ?? '' }}">
                                                {{ $t->mes_label ?? '—' }}
                                            </th>
                                        @endforeach
                                        <th class="text-right font-weight-bold" style="width: 90px;">Total</th>
                                        <th class="text-right" style="width: 120px;">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($ranking as $pos => $fila)
                                    <tr>
                                        <td><strong>{{ $pos + 1 }}</strong></td>
                                        <td>
                                            <img src="{{ asset($fila->foto ?? 'images/jugador_img.png') }}" alt="" class="ranking-foto mr-2" onerror="this.src='{{ asset('images/jugador_img.png') }}';">
                                            {{ $fila->nombre ?? '' }} {{ $fila->apellido ?? '' }}
                                        </td>
                                        @foreach($torneos_ranking as $t)
                                            <td class="text-center">
                                                @isset($desglose_puntos[$fila->jugador_id][$t->id])
                                                    {{ number_format($desglose_puntos[$fila->jugador_id][$t->id], 0, ',', '.') }}
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endisset
                                            </td>
                                        @endforeach
                                        <td class="text-right font-weight-bold">{{ number_format($fila->puntos_totales, 0, ',', '.') }}</td>
                                        <td class="text-right text-nowrap">
                                            <button type="button"
                                                    class="btn btn-outline-success btn-sm"
                                                    title="Subir de categoría (divide puntos por 2)"
                                                    onclick="moverCategoriaRanking({{ (int) $fila->jugador_id }}, 'up')">
                                                <i class="fas fa-arrow-up"></i>
                                            </button>
                                            <button type="button"
                                                    class="btn btn-outline-warning btn-sm"
                                                    title="Bajar de categoría (divide puntos por 2)"
                                                    onclick="moverCategoriaRanking({{ (int) $fila->jugador_id }}, 'down')">
                                                <i class="fas fa-arrow-down"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @elseif(!$categorias->isEmpty() && !$temporadas->isEmpty())
                        <p class="text-muted mb-0">No hay datos de ranking para {{ $categoria_seleccionada }}º categoría en la temporada {{ $temporada_seleccionada }}.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Referencias de puntuación -->
<div class="modal fade" id="modalReferenciasPuntuacion" tabindex="-1" role="dialog" aria-labelledby="modalReferenciasPuntuacionLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalReferenciasPuntuacionLabel">Referencias de puntuación</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Puntos que se asignan por defecto según la posición en el torneo. Podés editar el nombre y los puntos.</p>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 50px;">Orden</th>
                                <th>Nombre</th>
                                <th style="width: 100px;">Puntos</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-referencias-puntuacion">
                            @forelse($referencias_puntuacion as $ref)
                            <tr data-id="{{ $ref->id }}">
                                <td class="align-middle">{{ $ref->orden }}</td>
                                <td><input type="text" class="form-control form-control-sm ref-nombre" value="{{ $ref->nombre }}" data-id="{{ $ref->id }}"></td>
                                <td><input type="number" min="0" class="form-control form-control-sm ref-puntos" value="{{ $ref->puntos }}" data-id="{{ $ref->id }}"></td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-muted text-center">No hay referencias cargadas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="btn-guardar-referencias">
                    <i class="fa fa-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    $('#btn-referencias-puntuacion').on('click', function() {
        $('#modalReferenciasPuntuacion').modal('show');
    });
    $('#btn-guardar-referencias').on('click', function() {
        var items = [];
        $('#tbody-referencias-puntuacion tr').each(function() {
            var id = $(this).data('id');
            var nombre = $(this).find('.ref-nombre').val();
            var puntos = parseInt($(this).find('.ref-puntos').val(), 10);
            if (isNaN(puntos)) puntos = 0;
            items.push({ id: id, nombre: nombre, puntos: puntos });
        });
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Guardando...');
        $.ajax({
            url: '{{ route("guardarreferenciaspuntuacion") }}',
            type: 'POST',
            data: { items: items, _token: '{{ csrf_token() }}' },
            dataType: 'json',
            success: function(res) {
                btn.prop('disabled', false).html('<i class="fa fa-save"></i> Guardar');
                if (res.success) {
                    if (typeof mostrarSnackbar === 'function') mostrarSnackbar(res.message || 'Guardado.');
                    else alert(res.message || 'Guardado.');
                    $('#modalReferenciasPuntuacion').modal('hide');
                } else {
                    alert(res.message || 'Error al guardar.');
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fa fa-save"></i> Guardar');
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error al guardar.';
                alert(msg);
            }
        });
    });
});

function moverCategoriaRanking(jugadorId, direccion) {
    if (!jugadorId) return;
    var tipo = $('#tipo').val();
    var categoria = $('#categoria').val();
    var temporada = $('#temporada').val();
    var texto = (direccion === 'up') ? 'subir' : 'bajar';
    if (!confirm('¿Seguro que querés ' + texto + ' de categoría? (los puntos se dividen por 2)')) return;

    $.ajax({
        url: '{{ route("adminrankingmover") }}',
        type: 'POST',
        dataType: 'json',
        data: {
            jugador_id: jugadorId,
            direccion: direccion,
            tipo: tipo,
            categoria: categoria,
            temporada: temporada,
            _token: '{{ csrf_token() }}'
        },
        success: function(res) {
            if (res && res.success) {
                location.reload();
            } else {
                alert((res && res.message) ? res.message : 'Error');
            }
        },
        error: function(xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error';
            alert(msg);
        }
    });
}
</script>

@endsection
