@php
    $vid = $venta->id;
@endphp
<div class="ticket-body-inner" data-venta-id="{{ $vid }}">
    <div class="form-group">
        <label class="small font-weight-bold mb-1">Cliente</label>
        <input type="text" class="form-control ticket-input-nombre" value="{{ $venta->nombre_cliente }}" autocomplete="off">
        <small class="text-muted ticket-nombre-status"></small>
    </div>
    <div class="table-responsive mb-2">
        <table class="table table-sm table-bordered mb-0">
            <thead class="thead-light"><tr><th>Producto</th><th class="text-center">Cant.</th><th class="text-right">Subtotal</th><th class="text-center p-1" style="width:44px"></th></tr></thead>
            <tbody class="ticket-lines-tbody">
                @foreach($venta->detalles as $d)
                    <tr>
                        <td>{{ $d->producto?->nombre }}</td>
                        <td class="text-center">{{ $d->cantidad }}</td>
                        <td class="text-right">{{ $fmtMoney($d->subtotal) }}</td>
                        <td class="text-center p-1 align-middle">
                            <button type="button" class="btn btn-sm btn-outline-danger btn-ticket-remove-linea px-2 py-0 font-weight-bold" data-detalle-id="{{ $d->id }}" title="Quitar línea">−</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mb-3 ticket-add-product-block">
        <label class="small mb-1 d-block">Categoría</label>
        <div class="d-flex flex-wrap ticket-cat-pills align-items-center" style="gap:6px;">
            @foreach($categoriasVenta as $cat)
                @php
                    $abbr = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($cat->nombre, 0, 2));
                @endphp
                <button type="button"
                    class="btn btn-sm btn-outline-secondary ticket-cat-btn rounded-circle p-0 d-inline-flex align-items-center justify-content-center text-nowrap"
                    style="width:34px;height:34px;font-size:0.68rem;font-weight:700;letter-spacing:-0.02em;"
                    data-categoria-id="{{ $cat->id }}"
                    title="{{ $cat->nombre }}">{{ $abbr }}</button>
            @endforeach
        </div>
        <div class="form-row align-items-end mt-2">
            <div class="form-group col-md-8 mb-2 mb-md-0">
                <label class="small mb-1">Producto</label>
                <select class="form-control ticket-select-producto" disabled>
                    <option value="">— Elegí una categoría —</option>
                </select>
            </div>
            <div class="form-group col-md-2 mb-2 mb-md-0">
                <label class="small mb-1">Cant.</label>
                <input type="number" class="form-control ticket-input-cantidad" value="1" min="1" step="1">
            </div>
            <div class="form-group col-md-2 mb-0">
                <label class="small mb-1 d-none d-md-block">&nbsp;</label>
                <button type="button" class="btn btn-outline-primary btn-block btn-ticket-add-linea font-weight-bold" style="font-size:1.15rem;line-height:1.2;" title="Agregar producto">+</button>
            </div>
        </div>
    </div>
    <div class="d-flex flex-wrap align-items-center mb-2">
        <span class="font-weight-bold mr-2">Total:</span>
        <span class="h5 mb-0 text-primary ticket-total">{{ $fmtMoney($venta->precio_total) }}</span>
    </div>
    <div class="d-flex flex-wrap">
        <form method="post" action="{{ route('admincaja.venta.pago', $venta) }}" class="mr-2 mb-2 form-ticket-pago" data-venta-id="{{ $vid }}">
            @csrf
            <input type="hidden" name="metodo_pago" value="efectivo">
            <button type="submit" class="btn btn-success btn-ticket-pay" @if((float)$venta->precio_total <= 0) disabled @endif>Efectivo</button>
        </form>
        <form method="post" action="{{ route('admincaja.venta.pago', $venta) }}" class="mr-2 mb-2 form-ticket-pago" data-venta-id="{{ $vid }}">
            @csrf
            <input type="hidden" name="metodo_pago" value="transferencia">
            <button type="submit" class="btn btn-info btn-ticket-pay" @if((float)$venta->precio_total <= 0) disabled @endif>Transferencia</button>
        </form>
        <button type="button" class="btn btn-secondary mb-2 btn-ticket-guardar">Guardar</button>
    </div>
    <small class="text-muted d-block">Los productos se guardan con <strong>+</strong>. Podés quitar una línea con <strong>−</strong>. El nombre se guarda con <strong>Guardar</strong> o al salir del campo cliente.</small>
</div>
