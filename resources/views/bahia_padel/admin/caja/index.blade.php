@extends('bahia_padel/admin/plantilla')

@section('title_header','Caja')

@section('contenedor')
<style>
.ticket-cat-btn { border-width: 2px; transition: transform .12s ease, box-shadow .12s ease; }
.ticket-cat-btn:hover { transform: scale(1.06); box-shadow: 0 2px 6px rgba(78,115,223,.25); }
.ticket-cat-btn.active { border-color: #4e73df; }
.ticket-card-panel { display: none; }
.ticket-card-panel.is-open { display: block; }
.caja-stat-trigger { cursor: pointer; transition: transform .12s ease, box-shadow .12s ease; }
.caja-stat-trigger:hover { transform: translateY(-1px); box-shadow: 0 0.35rem 0.75rem rgba(0,0,0,.12) !important; }
</style>
@php
    $fmtMoney = fn ($n) => '$' . number_format((float) $n, 2, ',', '.');
@endphp

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
@endif

<div class="container-fluid body_admin">
    <div class="row mb-3 align-items-end">
        <div class="col-lg-8 col-md-7 mb-2 mb-md-0">
            <form method="get" action="{{ route('admincaja') }}" class="form-inline flex-wrap align-items-center" id="form-caja-fecha">
                <label for="caja-fecha-consulta" class="mb-0 mr-2 font-weight-bold text-gray-800">Caja del día</label>
                <input type="date"
                    name="fecha"
                    id="caja-fecha-consulta"
                    class="form-control"
                    value="{{ $fechaCaja }}"
                    max="{{ \Carbon\Carbon::today()->format('Y-m-d') }}"
                    onchange="if (this.form) this.form.submit();">
                <button type="submit" class="btn btn-primary ml-2">Ver</button>
                @if(!$fechaCajaEsHoy)
                    <a href="{{ route('admincaja') }}" class="btn btn-outline-secondary ml-2">Volver a hoy</a>
                    <span class="small text-muted ml-2">Consulta: {{ $fechaCajaLabel }} (solo lectura: no podés abrir tickets nuevos).</span>
                @else
                    <span class="small text-muted ml-2 d-none d-md-inline">{{ $fechaCajaLabel }}</span>
                @endif
            </form>
        </div>
    </div>
    <div class="row mb-2">
        <div class="col-md-2 col-sm-6 mb-2">
            <div class="card border-left-primary shadow h-100 py-2 caja-stat-trigger" data-resumen="ventas-hoy" data-titulo="Ventas del {{ $fechaCajaLabel }} (detalle)">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Ventas del día</div>
                    <div class="h5 mb-0" id="caja-stat-transacciones">{{ $statsHoy['transacciones'] }} mov.</div>
                    <span class="small text-muted">Tocá para listado</span>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6 mb-2">
            <div class="card border-left-success shadow h-100 py-2 caja-stat-trigger" data-resumen="total-hoy" data-titulo="Total facturado el {{ $fechaCajaLabel }} (todas las ventas del día)">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total del día</div>
                    <div class="h5 mb-0" id="caja-stat-monto-total">{{ $fmtMoney($statsHoy['monto_total']) }}</div>
                    <span class="small text-muted">Tocá para listado</span>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6 mb-2">
            <div class="card border-left-info shadow h-100 py-2 caja-stat-trigger" data-resumen="efectivo-hoy" data-titulo="Ventas en efectivo ({{ $fechaCajaLabel }})">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Efectivo</div>
                    <div class="h5 mb-0" id="caja-stat-efectivo">{{ $fmtMoney($statsHoy['efectivo']) }}</div>
                    <span class="small text-muted">Tocá para listado</span>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6 mb-2">
            <div class="card border-left-secondary shadow h-100 py-2 caja-stat-trigger" data-resumen="transfer-hoy" data-titulo="Ventas por transferencia ({{ $fechaCajaLabel }})">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Transfer.</div>
                    <div class="h5 mb-0" id="caja-stat-transferencia">{{ $fmtMoney($statsHoy['transferencia']) }}</div>
                    <span class="small text-muted">Tocá para listado</span>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6 mb-2">
            <div class="card border-left-success shadow h-100 py-2 caja-stat-trigger" data-resumen="cobrado-hoy" data-titulo="Cobrado el {{ $fechaCajaLabel }} (ventas pagadas)">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Cobrado</div>
                    <div class="h5 mb-0" id="caja-stat-pagado">{{ $fmtMoney($statsHoy['pagado']) }}</div>
                    <span class="small text-muted">Tocá para listado</span>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6 mb-2">
            <div class="card border-left-warning shadow h-100 py-2 caja-stat-trigger" data-resumen="pendientes-dia" data-titulo="Pendientes con fecha de venta {{ $fechaCajaLabel }}">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pendiente (día)</div>
                    <div class="h5 mb-0" id="caja-stat-pendiente-dia">{{ $fmtMoney($statsHoy['pendiente']) }}</div>
                    <span class="small text-muted">Tocá para listado</span>
                </div>
            </div>
        </div>
    </div>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-left-danger shadow h-100 py-2 caja-stat-trigger" data-resumen="pendientes-saldo" data-titulo="Ventas pendientes de cobro (con saldo, todas las fechas)">
                <div class="card-body py-2 d-flex flex-wrap align-items-center justify-content-between">
                    <div>
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Pendientes de cobro (saldo)</div>
                        <div class="small mb-0 text-muted">Incluye deudas de días anteriores. Tocá para ver el listado y Ver / Cobrar.</div>
                    </div>
                    <div class="h5 mb-0 text-danger font-weight-bold" id="caja-stat-pendientes-saldo">{{ $pendientes->count() }} venta(s)</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4 d-none" id="caja-resumen-detalle">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header d-flex justify-content-between align-items-center py-2 bg-white border-bottom">
                    <span class="font-weight-bold text-primary m-0" id="caja-resumen-titulo"></span>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="caja-resumen-cerrar">Cerrar</button>
                </div>
                <div class="card-body p-0">
                    <div class="resumen-tabla d-none" id="resumen-data-ventas-hoy">
                        @include('bahia_padel.admin.caja._tabla_listado_ventas', ['ventas' => $listaVentasHoy, 'fmtMoney' => $fmtMoney, 'mostrarAccionesVerCobrar' => false])
                    </div>
                    <div class="resumen-tabla d-none" id="resumen-data-total-hoy">
                        @include('bahia_padel.admin.caja._tabla_listado_ventas', ['ventas' => $listaVentasHoy, 'fmtMoney' => $fmtMoney, 'mostrarAccionesVerCobrar' => false])
                    </div>
                    <div class="resumen-tabla d-none" id="resumen-data-efectivo-hoy">
                        @include('bahia_padel.admin.caja._tabla_listado_ventas', ['ventas' => $listaEfectivoHoy, 'fmtMoney' => $fmtMoney, 'mostrarAccionesVerCobrar' => false])
                    </div>
                    <div class="resumen-tabla d-none" id="resumen-data-transfer-hoy">
                        @include('bahia_padel.admin.caja._tabla_listado_ventas', ['ventas' => $listaTransferHoy, 'fmtMoney' => $fmtMoney, 'mostrarAccionesVerCobrar' => false])
                    </div>
                    <div class="resumen-tabla d-none" id="resumen-data-cobrado-hoy">
                        @include('bahia_padel.admin.caja._tabla_listado_ventas', ['ventas' => $listaCobradoHoy, 'fmtMoney' => $fmtMoney, 'mostrarAccionesVerCobrar' => false])
                    </div>
                    <div class="resumen-tabla d-none" id="resumen-data-pendientes-dia">
                        @include('bahia_padel.admin.caja._tabla_listado_ventas', ['ventas' => $listaPendienteHoy, 'fmtMoney' => $fmtMoney, 'mostrarAccionesVerCobrar' => false])
                    </div>
                    <div class="resumen-tabla d-none" id="resumen-data-pendientes-saldo">
                        @include('bahia_padel.admin.caja._tabla_listado_ventas', ['ventas' => $pendientes, 'fmtMoney' => $fmtMoney, 'mostrarAccionesVerCobrar' => false])
                    </div>
                </div>
            </div>
        </div>
    </div>
    @if($fechaCajaEsHoy)
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex flex-wrap justify-content-center justify-content-md-start" style="gap:10px;">
                @foreach(['Cancha 1', 'Cancha 2', 'Cancha 3', 'Particular'] as $etiq)
                    <button type="button" class="btn btn-outline-primary btn-cancha-caja px-4 py-2" style="min-width:140px;" data-cancha-id="{{ $canchasCajaIds[$etiq] ?? '' }}">
                        {{ $etiq }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <div class="row mb-4 d-none" id="row-panel-nuevo-ticket">
        <div class="col-lg-10 col-xl-8">
            <div class="card shadow border-left-info" id="panel-nuevo-ticket">
                <div class="card-header py-2 font-weight-bold text-primary">Nueva venta (se guarda en el servidor al abrir el ticket)</div>
                <div class="card-body">
                    @if($productosVenta->isEmpty())
                        <p class="text-warning mb-0">No hay productos con stock. Cargá en <a href="{{ route('adminstock') }}">Stock</a> para poder vender.</p>
                    @else
                    <p class="small text-muted mb-2">Escribí el nombre del cliente (podés incluir horario u otra referencia en el mismo campo) y tocá <strong>Abrir ticket</strong>.</p>
                    <div class="form-row align-items-end">
                        <div class="form-group col-md-6 mb-2">
                            <label class="small font-weight-bold mb-1">Cliente</label>
                            <input type="text" id="nuevo-nombre-cliente" class="form-control" placeholder="Ej. Rodri · Cancha 1 17:00" autocomplete="off" disabled>
                        </div>
                        <div class="form-group col-md-6 mb-2">
                            <label class="small text-muted mb-1">Cancha elegida</label>
                            <input type="text" id="nuevo-cancha-label" class="form-control bg-light" readonly placeholder="Tocá Cancha 1–3 o Particular" value="">
                        </div>
                    </div>
                    <input type="hidden" id="nuevo-stock-cancha-id" value="">
                    <button type="button" class="btn btn-primary" id="btn-abrir-ticket" disabled>Abrir ticket</button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="d-flex flex-wrap align-items-baseline justify-content-between mb-2">
                <h6 class="font-weight-bold text-gray-800 mb-0">Tickets en curso — {{ $fechaCajaLabel }}</h6>
                <button type="button" class="btn btn-link btn-sm p-0 text-primary shadow-none" id="btn-toggle-lista-tickets" aria-expanded="false">Desplegar todos</button>
            </div>
            <div class="row" id="lista-tickets-abiertos">
                @foreach($ticketsAbiertos as $venta)
                <div class="col-lg-4 col-md-6 mb-3 d-flex">
                <div class="card mb-0 ticket-card shadow flex-fill w-100" data-venta-id="{{ $venta->id }}">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center ticket-card-header-toggle" style="cursor:pointer">
                        <div>
                            <strong class="ticket-card-nombre">{{ $venta->nombre_cliente }}</strong>
                            <span class="text-muted small ml-2 ticket-card-cancha-meta">{{ $venta->cancha?->nombre }}</span>
                        </div>
                        <div>
                            <span class="badge badge-primary ticket-card-total">{{ $fmtMoney($venta->precio_total) }}</span>
                            <span class="small text-muted ml-1">#{{ $venta->id }}</span>
                        </div>
                    </div>
                    <div id="ticket-collapse-{{ $venta->id }}" class="ticket-card-panel">
                        <div class="card-body text-dark pt-3">
                            @include('bahia_padel.admin.caja._ticket_body', ['venta' => $venta, 'categoriasVenta' => $categoriasVenta, 'fmtMoney' => $fmtMoney])
                        </div>
                    </div>
                </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@php
    $cajaCategoriasJson = $categoriasVenta->map(function ($c) {
        return [
            'id' => $c->id,
            'nombre' => $c->nombre,
            'abbr' => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($c->nombre, 0, 2)),
        ];
    })->values();
    $cajaProductosJson = $productosVenta->map(function ($p) use ($fmtMoney) {
        return [
            'id' => $p->id,
            'categoria_id' => $p->stock_categoria_id,
            'label' => $p->nombre.' (stock '.$p->stock_actual.') — '.$fmtMoney($p->precio_unitario),
        ];
    })->values();
@endphp
<script>
(function() {
    var csrf = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrf ? csrf.getAttribute('content') : '';

    /** Rutas respecto al host actual (evita APP_URL distinto y rutas relativas mal resueltas). */
    function adminCajaBasePath() {
        var path = (window.location.pathname || '').replace(/\/$/, '');
        if (/\/admin_caja(\/|$)/.test(path)) {
            return path.replace(/\/admin_caja.*$/, '/admin_caja');
        }
        return path + '/admin_caja';
    }
    function borradorUrl() {
        return adminCajaBasePath() + '/venta/borrador';
    }
    function lineaUrl(ventaId) {
        return adminCajaBasePath() + '/venta/' + ventaId + '/linea';
    }
    function lineaDestroyUrl(ventaId, detalleId) {
        return adminCajaBasePath() + '/venta/' + ventaId + '/linea/' + detalleId;
    }
    function updateUrl(ventaId) {
        return adminCajaBasePath() + '/venta/' + ventaId;
    }
    function pagoUrl(ventaId) {
        return adminCajaBasePath() + '/venta/' + ventaId + '/pago';
    }
    function resumenCajaUrl() {
        return adminCajaBasePath() + '/resumen';
    }
    /** Refresca números y HTML de todas las tablas del panel (misma fecha que el datepicker). */
    function fetchCajaResumenAplicar() {
        var fechaEl = document.getElementById('caja-fecha-consulta');
        var q = '';
        if (fechaEl && fechaEl.value) q = '?fecha=' + encodeURIComponent(fechaEl.value);
        fetch(resumenCajaUrl() + q, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(parseFetchResponse).then(function(x) {
            if (!x.ok || !x.j) return;
            applyCajaResumen(x.j);
        }).catch(function() {});
    }

    function parseFetchResponse(r) {
        return r.text().then(function(text) {
            var j = null;
            if (text) {
                try {
                    j = JSON.parse(text);
                } catch (e) {
                    j = { message: 'Respuesta inválida (HTTP ' + r.status + '). ' + text.replace(/<[^>]+>/g, ' ').trim().slice(0, 200) };
                }
            } else {
                j = { message: 'Sin respuesta (HTTP ' + r.status + ')' };
            }
            return { ok: r.ok, status: r.status, j: j };
        });
    }

    window.CAJA_CATEGORIAS = @json($cajaCategoriasJson);
    window.CAJA_PRODUCTOS = @json($cajaProductosJson);

    function escapeHtml(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function fillProductSelectForCategory(sel, categoriaId) {
        if (!sel) return;
        var opts = '<option value="">— Elegir producto —</option>';
        (window.CAJA_PRODUCTOS || []).filter(function(p) {
            return String(p.categoria_id) === String(categoriaId);
        }).forEach(function(p) {
            opts += '<option value="' + p.id + '">' + escapeHtml(p.label) + '</option>';
        });
        sel.innerHTML = opts;
        sel.disabled = false;
    }

    function categoriasPillsHtml() {
        var h = '';
        (window.CAJA_CATEGORIAS || []).forEach(function(c) {
            h += '<button type="button" class="btn btn-sm btn-outline-secondary ticket-cat-btn rounded-circle p-0 d-inline-flex align-items-center justify-content-center text-nowrap" '
                + 'style="width:34px;height:34px;font-size:0.68rem;font-weight:700;letter-spacing:-0.02em;" '
                + 'data-categoria-id="' + c.id + '" title="' + escapeHtml(c.nombre) + '">'
                + escapeHtml(c.abbr) + '</button>';
        });
        return h;
    }

    function wireProductPicker(inner) {
        if (!inner || inner._pickerWired) return;
        inner._pickerWired = true;
        var pills = inner.querySelectorAll('.ticket-cat-btn');
        var sel = inner.querySelector('.ticket-select-producto');
        if (!pills.length || !sel) return;
        pills.forEach(function(btn) {
            btn.addEventListener('click', function() {
                pills.forEach(function(b) {
                    b.classList.remove('active', 'btn-primary');
                    b.classList.add('btn-outline-secondary');
                });
                btn.classList.add('active', 'btn-primary');
                btn.classList.remove('btn-outline-secondary');
                var cid = btn.getAttribute('data-categoria-id');
                fillProductSelectForCategory(sel, cid);
            });
        });
    }

    function jsonHeaders() {
        return {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        };
    }

    /** Alinea el resumen de caja con la fecha consultada en pantalla (#caja-fecha-consulta). */
    function mergeCajaFecha(body) {
        var fechaEl = document.getElementById('caja-fecha-consulta');
        if (fechaEl && fechaEl.value) body.caja_fecha = fechaEl.value;
        return body;
    }

    function refreshLinesTbody(tbody, detalles) {
        tbody.innerHTML = '';
        detalles.forEach(function(d) {
            var tr = document.createElement('tr');
            tr.innerHTML = '<td>' + escapeHtml(d.producto_nombre || '') + '</td>'
                + '<td class="text-center">' + d.cantidad + '</td>'
                + '<td class="text-right">' + escapeHtml(d.subtotal_fmt) + '</td>'
                + '<td class="text-center p-1 align-middle">'
                + '<button type="button" class="btn btn-sm btn-outline-danger btn-ticket-remove-linea px-2 py-0 font-weight-bold" data-detalle-id="' + d.id + '" title="Quitar línea">−</button>'
                + '</td>';
            tbody.appendChild(tr);
        });
    }

    function syncPayButtons(cardRoot, precioTotal) {
        var ok = precioTotal > 0;
        cardRoot.querySelectorAll('.btn-ticket-pay').forEach(function(b) {
            b.disabled = !ok;
        });
    }

    function applyVentaJson(cardRoot, venta) {
        var hNombre = cardRoot.querySelector('.ticket-card-nombre');
        var hTot = cardRoot.querySelector('.ticket-card-total');
        var hMeta = cardRoot.querySelector('.ticket-card-cancha-meta');
        if (hNombre) hNombre.textContent = venta.nombre_cliente;
        if (hTot) hTot.textContent = venta.precio_total_fmt;
        if (hMeta) hMeta.textContent = venta.cancha_nombre ? String(venta.cancha_nombre) : '';
        var inner = cardRoot.querySelector('.ticket-body-inner[data-venta-id="' + venta.id + '"]');
        if (!inner) return;
        var inpNombre = inner.querySelector('.ticket-input-nombre');
        if (inpNombre) inpNombre.value = venta.nombre_cliente;
        var tTot = inner.querySelector('.ticket-total');
        if (tTot) tTot.textContent = venta.precio_total_fmt;
        var tbody = inner.querySelector('.ticket-lines-tbody');
        if (tbody) refreshLinesTbody(tbody, venta.detalles || []);
        syncPayButtons(inner, venta.precio_total);
    }

    function applyCajaResumen(res) {
        if (!res) return;
        var el;
        el = document.getElementById('caja-stat-transacciones');
        if (el) el.textContent = res.transacciones + ' mov.';
        el = document.getElementById('caja-stat-monto-total');
        if (el) el.textContent = res.monto_total_fmt;
        el = document.getElementById('caja-stat-efectivo');
        if (el) el.textContent = res.efectivo_fmt;
        el = document.getElementById('caja-stat-transferencia');
        if (el) el.textContent = res.transferencia_fmt;
        el = document.getElementById('caja-stat-pagado');
        if (el) el.textContent = res.pagado_fmt;
        el = document.getElementById('caja-stat-pendiente-dia');
        if (el) el.textContent = res.pendiente_dia_fmt;
        el = document.getElementById('caja-stat-pendientes-saldo');
        if (el) el.textContent = res.pendientes_saldo_count + ' venta(s)';
        var tablasResumen = [
            ['ventas-hoy', 'html_ventas_hoy'],
            ['total-hoy', 'html_total_hoy'],
            ['efectivo-hoy', 'html_efectivo_hoy'],
            ['transfer-hoy', 'html_transfer_hoy'],
            ['cobrado-hoy', 'html_cobrado_hoy'],
            ['pendientes-dia', 'html_pendientes_dia'],
            ['pendientes-saldo', 'html_pendientes_saldo'],
        ];
        tablasResumen.forEach(function(pair) {
            var wrap = document.getElementById('resumen-data-' + pair[0]);
            var k = pair[1];
            if (wrap && Object.prototype.hasOwnProperty.call(res, k) && typeof res[k] === 'string') {
                wrap.innerHTML = res[k];
            }
        });
    }

    function patchNombre(ventaId, nombre, statusEl, cb) {
        if (statusEl) statusEl.textContent = 'Guardando…';
        fetch(updateUrl(ventaId), {
            method: 'PATCH',
            headers: jsonHeaders(),
            body: JSON.stringify({ _token: csrfToken, nombre_cliente: nombre })
        }).then(parseFetchResponse).then(function(x) {
            if (!x.ok) {
                var msg = (x.j && x.j.message) || (x.j && x.j.errors && (typeof x.j.errors === 'object') && JSON.stringify(x.j.errors));
                if (statusEl) statusEl.textContent = msg || 'Error';
                return;
            }
            if (statusEl) statusEl.textContent = 'Guardado';
            if (cb) cb();
            setTimeout(function() { if (statusEl) statusEl.textContent = ''; }, 2000);
        }).catch(function(e) {
            if (statusEl) statusEl.textContent = (e && e.message) ? e.message : 'Sin conexión';
        });
    }

    function wireTicketCard(card) {
        var inner = card.querySelector('.ticket-body-inner');
        if (!inner) return;
        var ventaId = inner.getAttribute('data-venta-id');
        var nombreInput = inner.querySelector('.ticket-input-nombre');
        var statusNombre = inner.querySelector('.ticket-nombre-status');
        var addBtn = inner.querySelector('.btn-ticket-add-linea');
        var sel = inner.querySelector('.ticket-select-producto');
        var cantInp = inner.querySelector('.ticket-input-cantidad');
        var guardarBtn = inner.querySelector('.btn-ticket-guardar');

        if (nombreInput && !nombreInput._wired) {
            nombreInput._wired = true;
            nombreInput.addEventListener('blur', function() {
                patchNombre(ventaId, nombreInput.value.trim() || '(Sin nombre)', statusNombre, function() {
                    var h = card.querySelector('.ticket-card-nombre');
                    if (h) h.textContent = nombreInput.value.trim() || '(Sin nombre)';
                });
            });
        }
        if (guardarBtn && !guardarBtn._wired) {
            guardarBtn._wired = true;
            guardarBtn.addEventListener('click', function() {
                patchNombre(ventaId, nombreInput ? nombreInput.value.trim() || '(Sin nombre)' : '', statusNombre, function() {
                    var h = card.querySelector('.ticket-card-nombre');
                    if (h && nombreInput) h.textContent = nombreInput.value.trim() || '(Sin nombre)';
                });
            });
        }
        if (addBtn && !addBtn._wired) {
            addBtn._wired = true;
            addBtn.addEventListener('click', function() {
                var pid = sel && sel.value;
                var qty = cantInp ? parseInt(cantInp.value, 10) : 0;
                if (!pid) { alert('Elegí una categoría y un producto.'); return; }
                if (!qty || qty < 1) { alert('Cantidad inválida.'); return; }
                addBtn.disabled = true;
                fetch(lineaUrl(ventaId), {
                    method: 'POST',
                    headers: jsonHeaders(),
                    body: JSON.stringify(mergeCajaFecha({
                        _token: csrfToken,
                        stock_producto_id: parseInt(pid, 10),
                        cantidad: qty
                    }))
                }).then(parseFetchResponse).then(function(x) {
                    addBtn.disabled = false;
                    if (!x.ok) {
                        var msg = (x.j && x.j.message) || (x.j && x.j.errors && (typeof x.j.errors === 'object') && Object.values(x.j.errors).flat().join(' '));
                        alert(msg || ('Error HTTP ' + x.status));
                        return;
                    }
                    if (!x.j.venta) {
                        alert('Respuesta sin datos de venta');
                        return;
                    }
                    if (x.j && x.j.resumen) applyCajaResumen(x.j.resumen);
                    applyVentaJson(card, x.j.venta);
                }).catch(function(e) {
                    addBtn.disabled = false;
                    alert((e && e.message) ? e.message : 'Error de red');
                });
            });
        }
        wireProductPicker(inner);
    }

    document.querySelectorAll('.ticket-card').forEach(wireTicketCard);

    var listaTicketsEl = document.getElementById('lista-tickets-abiertos');
    var btnToggleListaTickets = document.getElementById('btn-toggle-lista-tickets');
    function syncTicketsToggleBtn() {
        if (!btnToggleListaTickets || !listaTicketsEl) return;
        var algunAbierto = listaTicketsEl.querySelector('.ticket-card-panel.is-open');
        btnToggleListaTickets.textContent = algunAbierto ? 'Contraer todos' : 'Desplegar todos';
        btnToggleListaTickets.setAttribute('aria-expanded', algunAbierto ? 'true' : 'false');
    }
    if (btnToggleListaTickets && listaTicketsEl) {
        btnToggleListaTickets.addEventListener('click', function(e) {
            e.preventDefault();
            var panels = listaTicketsEl.querySelectorAll('.ticket-card-panel');
            var algunAbierto = listaTicketsEl.querySelector('.ticket-card-panel.is-open');
            panels.forEach(function(p) {
                if (algunAbierto) {
                    p.classList.remove('is-open');
                } else {
                    p.classList.add('is-open');
                }
            });
            syncTicketsToggleBtn();
        });
    }
    syncTicketsToggleBtn();
    if (listaTicketsEl) {
        listaTicketsEl.addEventListener('submit', function(e) {
            var form = e.target;
            if (!form.classList || !form.classList.contains('form-ticket-pago')) return;
            e.preventDefault();
            var btn = form.querySelector('button[type="submit"]');
            if (btn) btn.disabled = true;
            var metodoInp = form.querySelector('input[name="metodo_pago"]');
            var metodo = (metodoInp && metodoInp.value) ? metodoInp.value : 'efectivo';
            fetch(form.action, {
                method: 'POST',
                headers: jsonHeaders(),
                body: JSON.stringify(mergeCajaFecha({ _token: csrfToken, metodo_pago: metodo }))
            }).then(parseFetchResponse).then(function(x) {
                if (btn) btn.disabled = false;
                if (!x.ok) {
                    var msg = (x.j && x.j.message) || (x.j && x.j.errors && (typeof x.j.errors === 'object') && Object.values(x.j.errors).flat().join(' '));
                    alert(msg || ('Error HTTP ' + x.status));
                    return;
                }
                if (x.j && x.j.resumen) applyCajaResumen(x.j.resumen);
                var col = form.closest('.col-lg-4');
                if (col) col.remove();
                syncTicketsToggleBtn();
            }).catch(function(err) {
                if (btn) btn.disabled = false;
                alert((err && err.message) ? err.message : 'Error de red');
            });
        });
    }
    if (listaTicketsEl) {
        listaTicketsEl.addEventListener('click', function(e) {
            var rm = e.target.closest('.btn-ticket-remove-linea');
            if (rm) {
                e.preventDefault();
                e.stopPropagation();
                var card = rm.closest('.ticket-card');
                var inner = card && card.querySelector('.ticket-body-inner');
                var ventaId = inner && inner.getAttribute('data-venta-id');
                var detalleId = rm.getAttribute('data-detalle-id');
                if (!ventaId || !detalleId) return;
                rm.disabled = true;
                fetch(lineaDestroyUrl(ventaId, detalleId), {
                    method: 'DELETE',
                    headers: jsonHeaders(),
                    body: JSON.stringify(mergeCajaFecha({ _token: csrfToken }))
                }).then(parseFetchResponse).then(function(x) {
                    rm.disabled = false;
                    if (!x.ok) {
                        var msg = (x.j && x.j.message) || (x.j && x.j.errors && Object.values(x.j.errors).flat().join(' '));
                        alert(msg || ('Error HTTP ' + x.status));
                        return;
                    }
                    if (!x.j.venta) {
                        alert('Respuesta sin datos de venta');
                        return;
                    }
                    if (x.j && x.j.resumen) applyCajaResumen(x.j.resumen);
                    applyVentaJson(card, x.j.venta);
                }).catch(function(err) {
                    rm.disabled = false;
                    alert((err && err.message) ? err.message : 'Error de red');
                });
                return;
            }
            var header = e.target.closest('.ticket-card-header-toggle');
            if (!header) return;
            var card = header.closest('.ticket-card');
            if (!card) return;
            var panel = card.querySelector('.ticket-card-panel');
            if (!panel) return;
            panel.classList.toggle('is-open');
            syncTicketsToggleBtn();
        });
    }

    (function initResumenCaja() {
        var wrap = document.getElementById('caja-resumen-detalle');
        var tituloEl = document.getElementById('caja-resumen-titulo');
        var cerrar = document.getElementById('caja-resumen-cerrar');
        if (!wrap || !tituloEl) return;
        document.querySelectorAll('.caja-stat-trigger').forEach(function(stat) {
            stat.addEventListener('click', function() {
                var key = stat.getAttribute('data-resumen');
                var titulo = stat.getAttribute('data-titulo') || '';
                if (!key) return;
                document.querySelectorAll('.resumen-tabla').forEach(function(el) { el.classList.add('d-none'); });
                var panel = document.getElementById('resumen-data-' + key);
                if (panel) panel.classList.remove('d-none');
                tituloEl.textContent = titulo;
                wrap.classList.remove('d-none');
                wrap.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                fetchCajaResumenAplicar();
            });
        });
        if (cerrar) {
            cerrar.addEventListener('click', function() {
                wrap.classList.add('d-none');
                document.querySelectorAll('.resumen-tabla').forEach(function(el) { el.classList.add('d-none'); });
            });
        }
    })();

    function buildTicketCardHtml(venta) {
        var id = venta.id;
        var collapseId = 'ticket-collapse-' + id;
        var body = ''
            + '<div class="ticket-body-inner" data-venta-id="' + id + '">'
            + '<div class="form-group">'
            + '<label class="small font-weight-bold mb-1">Cliente</label>'
            + '<input type="text" class="form-control ticket-input-nombre" value="' + escapeHtml(venta.nombre_cliente) + '" autocomplete="off">'
            + '<small class="text-muted ticket-nombre-status"></small>'
            + '</div>'
            + '<div class="table-responsive mb-2">'
            + '<table class="table table-sm table-bordered mb-0">'
            + '<thead class="thead-light"><tr><th>Producto</th><th class="text-center">Cant.</th><th class="text-right">Subtotal</th><th class="text-center p-1" style="width:44px"></th></tr></thead>'
            + '<tbody class="ticket-lines-tbody"></tbody></table></div>'
            + '<div class="mb-3 ticket-add-product-block">'
            + '<label class="small mb-1 d-block">Categoría</label>'
            + '<div class="d-flex flex-wrap ticket-cat-pills align-items-center" style="gap:6px;">'
            + categoriasPillsHtml()
            + '</div>'
            + '<div class="form-row align-items-end mt-2">'
            + '<div class="form-group col-md-8 mb-2 mb-md-0"><label class="small mb-1">Producto</label>'
            + '<select class="form-control ticket-select-producto" disabled><option value="">— Elegí una categoría —</option></select></div>'
            + '<div class="form-group col-md-2 mb-2 mb-md-0"><label class="small mb-1">Cant.</label>'
            + '<input type="number" class="form-control ticket-input-cantidad" value="1" min="1" step="1"></div>'
            + '<div class="form-group col-md-2 mb-0"><label class="small mb-1 d-none d-md-block">&nbsp;</label>'
            + '<button type="button" class="btn btn-outline-primary btn-block btn-ticket-add-linea font-weight-bold" style="font-size:1.15rem;line-height:1.2;" title="Agregar producto">+</button></div>'
            + '</div></div>'
            + '<div class="d-flex flex-wrap align-items-center mb-2">'
            + '<span class="font-weight-bold mr-2">Total:</span>'
            + '<span class="h5 mb-0 text-primary ticket-total">' + escapeHtml(venta.precio_total_fmt) + '</span></div>'
            + '<div class="d-flex flex-wrap">'
            + '<form method="post" action="' + escapeHtml(pagoUrl(id)) + '" class="mr-2 mb-2 form-ticket-pago" data-venta-id="' + id + '">'
            + '<input type="hidden" name="_token" value="' + escapeHtml(csrfToken) + '">'
            + '<input type="hidden" name="metodo_pago" value="efectivo">'
            + '<button type="submit" class="btn btn-success btn-ticket-pay" disabled>Efectivo</button></form>'
            + '<form method="post" action="' + escapeHtml(pagoUrl(id)) + '" class="mr-2 mb-2 form-ticket-pago" data-venta-id="' + id + '">'
            + '<input type="hidden" name="_token" value="' + escapeHtml(csrfToken) + '">'
            + '<input type="hidden" name="metodo_pago" value="transferencia">'
            + '<button type="submit" class="btn btn-info btn-ticket-pay" disabled>Transferencia</button></form>'
            + '<button type="button" class="btn btn-secondary mb-2 btn-ticket-guardar">Guardar</button></div>'
            + '<small class="text-muted d-block">Los productos se guardan con <strong>+</strong>. Podés quitar una línea con <strong>−</strong>. El nombre con <strong>Guardar</strong> o al salir del campo cliente.</small>'
            + '</div>';

        return ''
            + '<div class="col-lg-4 col-md-6 mb-3 d-flex">'
            + '<div class="card mb-0 ticket-card shadow flex-fill w-100" data-venta-id="' + id + '">'
            + '<div class="card-header py-2 d-flex justify-content-between align-items-center ticket-card-header-toggle" style="cursor:pointer">'
            + '<div><strong class="ticket-card-nombre">' + escapeHtml(venta.nombre_cliente) + '</strong> '
            + '<span class="text-muted small ml-2 ticket-card-cancha-meta">' + escapeHtml(venta.cancha_nombre || '') + '</span></div>'
            + '<div><span class="badge badge-primary ticket-card-total">' + escapeHtml(venta.precio_total_fmt) + '</span> '
            + '<span class="small text-muted ml-1">#' + id + '</span></div></div>'
            + '<div id="' + collapseId + '" class="ticket-card-panel is-open">'
            + '<div class="card-body text-dark pt-3">' + body + '</div></div></div></div>';
    }

    var selectedCanchaId = '';
    var selectedLabel = '';
    var nuevoNombre = document.getElementById('nuevo-nombre-cliente');
    var nuevoCanchaLabel = document.getElementById('nuevo-cancha-label');
    var nuevoStockCanchaId = document.getElementById('nuevo-stock-cancha-id');
    var btnAbrir = document.getElementById('btn-abrir-ticket');

    function syncNuevoPanel() {
        var ok = selectedCanchaId && nuevoNombre && nuevoNombre.value.trim().length > 0;
        if (btnAbrir) btnAbrir.disabled = !ok;
        if (nuevoNombre) nuevoNombre.disabled = !selectedCanchaId;
    }

    document.querySelectorAll('.btn-cancha-caja').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var rowPanel = document.getElementById('row-panel-nuevo-ticket');
            if (rowPanel) {
                rowPanel.classList.remove('d-none');
                rowPanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
            document.querySelectorAll('.btn-cancha-caja').forEach(function(b) { b.classList.remove('active'); });
            btn.classList.add('active');
            selectedCanchaId = btn.getAttribute('data-cancha-id') || '';
            selectedLabel = btn.textContent.trim();
            if (nuevoStockCanchaId) nuevoStockCanchaId.value = selectedCanchaId;
            if (nuevoCanchaLabel) nuevoCanchaLabel.value = selectedLabel;
            syncNuevoPanel();
            if (nuevoNombre) nuevoNombre.focus();
        });
    });
    if (nuevoNombre) {
        nuevoNombre.addEventListener('input', syncNuevoPanel);
    }
    syncNuevoPanel();

    if (btnAbrir) {
        btnAbrir.addEventListener('click', function() {
            if (!selectedCanchaId || !nuevoNombre || !nuevoNombre.value.trim()) return;
            btnAbrir.disabled = true;
            fetch(borradorUrl(), {
                method: 'POST',
                headers: jsonHeaders(),
                body: JSON.stringify(mergeCajaFecha({
                    _token: csrfToken,
                    nombre_cliente: nuevoNombre.value.trim(),
                    stock_cancha_id: parseInt(selectedCanchaId, 10)
                }))
            }).then(parseFetchResponse).then(function(x) {
                btnAbrir.disabled = false;
                if (!x.ok) {
                    var msg = (x.j && x.j.message) || (x.j && x.j.errors && Object.values(x.j.errors).flat().join(' '));
                    alert(msg || 'No se pudo abrir el ticket');
                    return;
                }
                if (!x.j.venta) {
                    alert('Respuesta inválida');
                    return;
                }
                var v = x.j.venta;
                var list = document.getElementById('lista-tickets-abiertos');
                list.insertAdjacentHTML('afterbegin', buildTicketCardHtml(v));
                var newCard = list.querySelector('.ticket-card[data-venta-id="' + v.id + '"]');
                if (newCard) {
                    wireTicketCard(newCard);
                    applyVentaJson(newCard, v);
                }
                if (x.j && x.j.resumen) applyCajaResumen(x.j.resumen);
                nuevoNombre.value = '';
                document.querySelectorAll('.btn-cancha-caja').forEach(function(b) { b.classList.remove('active'); });
                selectedCanchaId = '';
                if (nuevoStockCanchaId) nuevoStockCanchaId.value = '';
                if (nuevoCanchaLabel) nuevoCanchaLabel.value = '';
                syncNuevoPanel();
                var rowPanelNuevo = document.getElementById('row-panel-nuevo-ticket');
                if (rowPanelNuevo) rowPanelNuevo.classList.add('d-none');
                syncTicketsToggleBtn();
            }).catch(function(e) {
                btnAbrir.disabled = false;
                alert((e && e.message) ? e.message : 'Error de red');
            });
        });
    }
})();
</script>
@endsection
