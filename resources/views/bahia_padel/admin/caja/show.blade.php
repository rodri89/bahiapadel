@extends('bahia_padel/admin/plantilla')

@section('title_header','Venta #' . $venta->id)

@section('contenedor')
@php
    $fmtMoney = fn ($n) => '$' . number_format((float) $n, 2, ',', '.');
@endphp

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="container-fluid body_admin text-dark">
    <p><a href="{{ route('admincaja') }}" class="btn btn-sm btn-secondary">&larr; Volver a Caja</a></p>

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Datos de la venta</h6></div>
        <div class="card-body row">
            <div class="col-md-6">
                <p><strong>Cliente:</strong> {{ $venta->nombre_cliente }}</p>
                <p><strong>Turno:</strong> {{ $venta->nombre_turno ?: '—' }}</p>
                <p><strong>Cancha:</strong> {{ $venta->cancha?->nombre }}</p>
                <p><strong>Fecha:</strong> {{ $venta->fecha_venta?->format('d/m/Y') }} <strong>Hora:</strong> {{ is_string($venta->hora_venta) ? substr($venta->hora_venta, 0, 5) : $venta->hora_venta }}</p>
            </div>
            <div class="col-md-6">
                <p><strong>Total:</strong> {{ $fmtMoney($venta->precio_total) }}</p>
                <p><strong>Método:</strong> {{ $venta->metodo_pago }}</p>
                <p><strong>Estado:</strong>
                    <span class="badge badge-{{ $venta->estado_pago === 'pagado' ? 'success' : 'warning' }}">{{ $venta->estado_pago }}</span>
                </p>
                @if($venta->fecha_pago)
                    <p><strong>Fecha pago:</strong> {{ $venta->fecha_pago->format('d/m/Y') }}</p>
                @endif
                @if($venta->referencia_pago)
                    <p><strong>Referencia:</strong> {{ $venta->referencia_pago }}</p>
                @endif
                @if($venta->notas)
                    <p><strong>Notas:</strong> {{ $venta->notas }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Productos</h6></div>
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead><tr><th>Producto</th><th>Cant.</th><th>P. unit.</th><th>Subtotal</th></tr></thead>
                <tbody>
                    @foreach($venta->detalles as $d)
                        <tr>
                            <td>{{ $d->producto?->nombre }}</td>
                            <td>{{ $d->cantidad }}</td>
                            <td>{{ $fmtMoney($d->precio_unitario) }}</td>
                            <td>{{ $fmtMoney($d->subtotal) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if($venta->pagos->isNotEmpty())
    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Historial de pagos</h6></div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead><tr><th>Fecha</th><th>Monto</th><th>Método</th><th>Ref.</th><th>Usuario</th></tr></thead>
                <tbody>
                    @foreach($venta->pagos as $p)
                        <tr>
                            <td>{{ $p->fecha_pago?->format('d/m/Y H:i') }}</td>
                            <td>{{ $fmtMoney($p->monto_pagado) }}</td>
                            <td>{{ $p->metodo_pago }}</td>
                            <td>{{ $p->referencia_pago }}</td>
                            <td>{{ $p->usuario_responsable }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if($venta->estado_pago === 'pendiente')
    <div class="card shadow border-success">
        <div class="card-header bg-light font-weight-bold">Registrar cobro</div>
        <div class="card-body">
            <form method="post" action="{{ route('admincaja.venta.pago', $venta) }}">
                @csrf
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Método de pago</label>
                        <select name="metodo_pago" class="form-control">
                            <option value="efectivo" @selected($venta->metodo_pago === 'efectivo')>Efectivo</option>
                            <option value="transferencia" @selected($venta->metodo_pago === 'transferencia')>Transferencia</option>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Fecha de pago</label>
                        <input type="date" name="fecha_pago" class="form-control" value="{{ now()->toDateString() }}">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Referencia / comprobante</label>
                        <input type="text" name="referencia_pago" class="form-control" value="{{ $venta->referencia_pago }}">
                    </div>
                </div>
                <div class="form-group">
                    <label>Notas</label>
                    <input type="text" name="notas" class="form-control">
                </div>
                <button type="submit" class="btn btn-success">Confirmar pago de {{ $fmtMoney($venta->precio_total) }}</button>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection
