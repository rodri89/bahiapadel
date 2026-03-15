@extends('bahia_padel/admin/plantilla')

@section('title_header','Calendario')

@section('contenedor')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Eventos del calendario</h6>
                    <button type="button" class="btn btn-success btn-sm" id="btn-nueva" onclick="mostrarFormNuevo()">
                        <i class="fas fa-plus"></i> Nuevo
                    </button>
                </div>
                <div class="card-body">
                    @if($eventos->isEmpty())
                        <p class="text-muted mb-0">No hay eventos. Agregá uno con el botón «Nuevo».</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Categoría</th>
                                        <th>Tipo</th>
                                        <th>Nombre</th>
                                        <th class="text-right">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($eventos as $e)
                                    <tr>
                                        <td>{{ $e->fecha ? $e->fecha->format('d/m/Y') : '-' }}</td>
                                        <td>{{ $e->categoria }}ª</td>
                                        <td>{{ $e->tipo_label }}</td>
                                        <td>{{ $e->nombre ?? '-' }}</td>
                                        <td class="text-right">
                                            <a href="{{ route('admincalendario') }}?editar={{ $e->id }}" class="btn btn-outline-primary btn-sm">Editar</a>
                                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="eliminar({{ $e->id }})">Eliminar</button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card shadow mb-4" id="card-form" style="{{ (isset($item) && $item) ? '' : 'display:none;' }}">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        {{ $item ? 'Editar evento' : 'Nuevo evento' }}
                    </h6>
                </div>
                <div class="card-body">
                    <form id="form-calendario">
                        @csrf
                        <input type="hidden" name="id" id="calendario_id" value="{{ $item ? $item->id : '' }}">
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">Fecha</label>
                            <div class="col-sm-4">
                                <input type="date" class="form-control" name="fecha" id="calendario_fecha" value="{{ $item && $item->fecha ? $item->fecha->format('Y-m-d') : '' }}" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">Categoría</label>
                            <div class="col-sm-4">
                                <select class="form-control" name="categoria" id="calendario_categoria" required>
                                    @for($i=1; $i<=7; $i++)
                                    <option value="{{ $i }}" {{ ($item && $item->categoria == $i) ? 'selected' : '' }}>{{ $i }}ª</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">Tipo</label>
                            <div class="col-sm-4">
                                <select class="form-control" name="tipo" id="calendario_tipo">
                                    <option value="mixto" {{ ($item && $item->tipo == 'mixto') ? 'selected' : '' }}>Mixto</option>
                                    <option value="femenino" {{ ($item && $item->tipo == 'femenino') ? 'selected' : '' }}>Damas</option>
                                    <option value="masculino" {{ ($item && $item->tipo == 'masculino') ? 'selected' : '' }}>Libre</option>
                                </select>
                                <small class="text-muted">Se guarda como femenino/masculino, en la home se muestra Damas/Libre</small>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">Nombre (opcional)</label>
                            <div class="col-sm-6">
                                <input type="text" class="form-control" name="nombre" id="calendario_nombre" value="{{ $item ? $item->nombre : '' }}" placeholder="Ej: Torneo marzo">
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-sm-10">
                                <button type="submit" class="btn btn-primary">Guardar</button>
                                <a href="{{ route('admincalendario') }}" class="btn btn-secondary">Cancelar</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function mostrarFormNuevo() {
    document.getElementById('card-form').style.display = 'block';
    document.getElementById('calendario_id').value = '';
    document.getElementById('calendario_fecha').value = '';
    document.getElementById('calendario_categoria').value = '1';
    document.getElementById('calendario_tipo').value = 'mixto';
    document.getElementById('calendario_nombre').value = '';
}

function eliminar(id) {
    if (!confirm('¿Eliminar este evento?')) return;
    $.post('{{ route("admincalendarioeliminar") }}', { id: id, _token: '{{ csrf_token() }}' }, function(r) {
        if (r.success) location.reload();
        else alert(r.message || 'Error');
    }, 'json').fail(function() { alert('Error al eliminar'); });
}

$('#form-calendario').on('submit', function(e) {
    e.preventDefault();
    var $btn = $(this).find('[type="submit"]');
    $btn.prop('disabled', true);
    $.post('{{ route("admincalendarioguardar") }}', {
        id: $('#calendario_id').val(),
        fecha: $('#calendario_fecha').val(),
        categoria: $('#calendario_categoria').val(),
        tipo: $('#calendario_tipo').val(),
        nombre: $('#calendario_nombre').val(),
        _token: '{{ csrf_token() }}'
    }, function(r) {
        $btn.prop('disabled', false);
        if (r.success) location.reload();
        else alert(r.message || 'Error');
    }, 'json').fail(function() {
        $btn.prop('disabled', false);
        alert('Error al guardar');
    });
});

@if($item)
document.getElementById('card-form').style.display = 'block';
@endif
</script>
@endsection
