<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\StockVentaService;
use App\StockCancha;
use App\StockDetalleVenta;
use App\StockProducto;
use App\StockVenta;
use Illuminate\Http\Request;

class CajaAdminController extends Controller
{
    private function etiquetasCanchaCaja(): array
    {
        return ['Cancha 1', 'Cancha 2', 'Cancha 3', 'Particular'];
    }

    private function mapaCanchasCaja(): array
    {
        $map = [];
        foreach ($this->etiquetasCanchaCaja() as $nombre) {
            $c = StockCancha::query()->firstOrCreate(
                ['nombre' => $nombre],
                ['activa' => true, 'descripcion' => null]
            );
            $map[$nombre] = $c->id;
        }

        return $map;
    }

    /**
     * Día de caja normalizado (Y-m-d), máximo hoy.
     */
    private function normalizarFechaCaja(?string $fecha): string
    {
        if ($fecha === null || $fecha === '') {
            return now()->toDateString();
        }
        try {
            $d = \Carbon\Carbon::createFromFormat('Y-m-d', $fecha)->startOfDay();
        } catch (\Throwable $e) {
            return now()->toDateString();
        }
        $hoy = now()->startOfDay();
        if ($d->gt($hoy)) {
            return $hoy->toDateString();
        }

        return $d->toDateString();
    }

    /**
     * Filtro por día de caja. `fecha_venta` es columna DATE: usar igualdad evita
     * diferencias de driver/timezone con whereDate(DATE(col)) en algunos hosts.
     */
    private function aplicarFiltroFechaVentas(\Illuminate\Database\Eloquent\Builder $q, string $fechaYmd): \Illuminate\Database\Eloquent\Builder
    {
        return $q->where('fecha_venta', $fechaYmd);
    }

    /**
     * Datos del panel superior + HTML de la tabla "pendientes con saldo" (para actualizar vía AJAX tras un cobro).
     *
     * @return array<string, mixed>
     */
    private function cajaResumenAjaxPayload(?string $fechaCaja = null): array
    {
        $dia = $this->normalizarFechaCaja($fechaCaja);
        $fmtMoney = fn ($n) => '$'.number_format((float) $n, 2, ',', '.');

        $statsHoy = [
            'transacciones' => $this->aplicarFiltroFechaVentas(StockVenta::query(), $dia)->count(),
            'monto_total' => (float) $this->aplicarFiltroFechaVentas(StockVenta::query(), $dia)->sum('precio_total'),
            'efectivo' => (float) $this->aplicarFiltroFechaVentas(StockVenta::query(), $dia)->where('metodo_pago', 'efectivo')->sum('precio_total'),
            'transferencia' => (float) $this->aplicarFiltroFechaVentas(StockVenta::query(), $dia)->where('metodo_pago', 'transferencia')->sum('precio_total'),
            'pagado' => (float) $this->aplicarFiltroFechaVentas(StockVenta::query(), $dia)->where('estado_pago', 'pagado')->sum('precio_total'),
            'pendiente' => (float) $this->aplicarFiltroFechaVentas(StockVenta::query(), $dia)->where('estado_pago', 'pendiente')->sum('precio_total'),
        ];

        $pendientes = StockVenta::query()
            ->with('cancha')
            ->where('estado_pago', 'pendiente')
            ->where('precio_total', '>', 0)
            ->orderBy('fecha_venta')
            ->get();

        $htmlPendientesSaldo = view('bahia_padel.admin.caja._tabla_listado_ventas', [
            'ventas' => $pendientes,
            'fmtMoney' => $fmtMoney,
            'mostrarAccionesVerCobrar' => false,
        ])->render();

        return [
            'transacciones' => (int) $statsHoy['transacciones'],
            'monto_total_fmt' => $fmtMoney($statsHoy['monto_total']),
            'efectivo_fmt' => $fmtMoney($statsHoy['efectivo']),
            'transferencia_fmt' => $fmtMoney($statsHoy['transferencia']),
            'pagado_fmt' => $fmtMoney($statsHoy['pagado']),
            'pendiente_dia_fmt' => $fmtMoney($statsHoy['pendiente']),
            'pendientes_saldo_count' => $pendientes->count(),
            'html_pendientes_saldo' => $htmlPendientesSaldo,
        ];
    }

    private function cajaFechaParaResumenDesdeRequest(Request $request): string
    {
        return $this->normalizarFechaCaja($request->input('caja_fecha'));
    }

    private function ventaToArray(StockVenta $venta, callable $fmtMoney): array
    {
        $venta->load(['detalles.producto', 'cancha']);

        return [
            'id' => $venta->id,
            'nombre_cliente' => $venta->nombre_cliente,
            'precio_total' => (float) $venta->precio_total,
            'precio_total_fmt' => $fmtMoney($venta->precio_total),
            'cancha_nombre' => $venta->cancha ? $venta->cancha->nombre : null,
            'detalles' => $venta->detalles->map(function ($d) use ($fmtMoney) {
                return [
                    'id' => $d->id,
                    'producto_nombre' => $d->producto ? $d->producto->nombre : null,
                    'cantidad' => (int) $d->cantidad,
                    'subtotal_fmt' => $fmtMoney($d->subtotal),
                ];
            })->values()->all(),
        ];
    }

    public function index(Request $request)
    {
        if ($request->filled('fecha')) {
            $request->validate([
                'fecha' => 'required|date_format:Y-m-d|before_or_equal:today',
            ]);
        }
        $fechaCaja = $this->normalizarFechaCaja($request->query('fecha'));
        $fechaCajaEsHoy = $fechaCaja === now()->toDateString();
        $fechaCajaLabel = \Carbon\Carbon::parse($fechaCaja)->format('d/m/Y');

        $canchasCajaIds = $this->mapaCanchasCaja();

        $productosVenta = StockProducto::query()
            ->where('activo', true)
            ->where('stock_actual', '>', 0)
            ->with('categoria')
            ->orderBy('nombre')
            ->get();

        $categoriasVenta = $productosVenta
            ->map(function ($p) {
                return $p->categoria;
            })
            ->filter()
            ->unique('id')
            ->sortBy('nombre')
            ->values();

        $pendientes = StockVenta::query()
            ->with('cancha')
            ->where('estado_pago', 'pendiente')
            ->where('precio_total', '>', 0)
            ->orderBy('fecha_venta')
            ->get();

        $statsHoy = [
            'transacciones' => $this->aplicarFiltroFechaVentas(StockVenta::query(), $fechaCaja)->count(),
            'monto_total' => (float) $this->aplicarFiltroFechaVentas(StockVenta::query(), $fechaCaja)->sum('precio_total'),
            'efectivo' => (float) $this->aplicarFiltroFechaVentas(StockVenta::query(), $fechaCaja)->where('metodo_pago', 'efectivo')->sum('precio_total'),
            'transferencia' => (float) $this->aplicarFiltroFechaVentas(StockVenta::query(), $fechaCaja)->where('metodo_pago', 'transferencia')->sum('precio_total'),
            'pagado' => (float) $this->aplicarFiltroFechaVentas(StockVenta::query(), $fechaCaja)->where('estado_pago', 'pagado')->sum('precio_total'),
            'pendiente' => (float) $this->aplicarFiltroFechaVentas(StockVenta::query(), $fechaCaja)->where('estado_pago', 'pendiente')->sum('precio_total'),
        ];

        $baseHoy = $this->aplicarFiltroFechaVentas(StockVenta::query()->with('cancha'), $fechaCaja);
        $listaVentasHoy = (clone $baseHoy)->orderByDesc('id')->get();
        $listaEfectivoHoy = (clone $baseHoy)->where('metodo_pago', 'efectivo')->orderByDesc('id')->get();
        $listaTransferHoy = (clone $baseHoy)->where('metodo_pago', 'transferencia')->orderByDesc('id')->get();
        $listaCobradoHoy = (clone $baseHoy)->where('estado_pago', 'pagado')->orderByDesc('id')->get();
        $listaPendienteHoy = (clone $baseHoy)->where('estado_pago', 'pendiente')->orderByDesc('id')->get();

        $ticketsAbiertos = StockVenta::query()
            ->with(['cancha', 'detalles.producto'])
            ->where('estado_pago', 'pendiente')
            ->where('fecha_venta', $fechaCaja)
            ->orderByDesc('updated_at')
            ->get();

        return view('bahia_padel.admin.caja.index', compact(
            'pendientes',
            'statsHoy',
            'listaVentasHoy',
            'listaEfectivoHoy',
            'listaTransferHoy',
            'listaCobradoHoy',
            'listaPendienteHoy',
            'productosVenta',
            'categoriasVenta',
            'canchasCajaIds',
            'ticketsAbiertos',
            'fechaCaja',
            'fechaCajaEsHoy',
            'fechaCajaLabel'
        ));
    }

    public function storeBorrador(Request $request, StockVentaService $ventaService)
    {
        $request->validate([
            'nombre_cliente' => 'required|string|max:100',
            'stock_cancha_id' => 'required|exists:stock_canchas,id',
            'caja_fecha' => 'nullable|date_format:Y-m-d|before_or_equal:today',
        ]);

        $fmtMoney = fn ($n) => '$'.number_format((float) $n, 2, ',', '.');

        try {
            // Misma fecha que muestra "Caja del día" (input + caja_fecha en JSON). Evita desfasaje UTC vs Argentina.
            $fechaVenta = $this->normalizarFechaCaja($request->input('caja_fecha'));

            $venta = $ventaService->crearVentaBorrador([
                'nombre_cliente' => $request->nombre_cliente,
                'stock_cancha_id' => (int) $request->stock_cancha_id,
                'fecha_venta' => $fechaVenta,
                'hora_venta' => now()->format('H:i:s'),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'venta' => $this->ventaToArray($venta, $fmtMoney),
            'resumen' => $this->cajaResumenAjaxPayload($this->cajaFechaParaResumenDesdeRequest($request)),
        ]);
    }

    public function storeLinea(Request $request, StockVenta $venta, StockVentaService $ventaService)
    {
        $request->validate([
            'stock_producto_id' => 'required|exists:stock_productos,id',
            'cantidad' => 'required|integer|min:1',
            'caja_fecha' => 'nullable|date_format:Y-m-d|before_or_equal:today',
        ]);

        $fmtMoney = fn ($n) => '$'.number_format((float) $n, 2, ',', '.');

        try {
            $venta = $ventaService->agregarLineaVenta(
                $venta,
                (int) $request->stock_producto_id,
                (int) $request->cantidad
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'venta' => $this->ventaToArray($venta, $fmtMoney),
            'resumen' => $this->cajaResumenAjaxPayload($this->cajaFechaParaResumenDesdeRequest($request)),
        ]);
    }

    public function destroyLinea(Request $request, StockVenta $venta, StockDetalleVenta $detalle, StockVentaService $ventaService)
    {
        if ((int) $detalle->stock_venta_id !== (int) $venta->id) {
            abort(404);
        }

        $request->validate([
            'caja_fecha' => 'nullable|date_format:Y-m-d|before_or_equal:today',
        ]);

        $fmtMoney = fn ($n) => '$'.number_format((float) $n, 2, ',', '.');

        try {
            $venta = $ventaService->eliminarLineaVenta($venta, (int) $detalle->id);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'venta' => $this->ventaToArray($venta, $fmtMoney),
            'resumen' => $this->cajaResumenAjaxPayload($this->cajaFechaParaResumenDesdeRequest($request)),
        ]);
    }

    public function updateBorrador(Request $request, StockVenta $venta, StockVentaService $ventaService)
    {
        $request->validate([
            'nombre_cliente' => 'required|string|max:100',
        ]);

        try {
            $ventaService->actualizarNombreBorrador($venta, $request->nombre_cliente);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true]);
    }

    public function storeVenta(Request $request, StockVentaService $ventaService)
    {
        $request->validate([
            'nombre_cliente' => 'required|string|max:100',
            'nombre_turno' => 'nullable|string|max:50',
            'stock_cancha_id' => 'required|exists:stock_canchas,id',
            'fecha_venta' => 'required|date',
            'hora_venta' => 'required|date_format:H:i',
            'metodo_pago' => 'required|in:efectivo,transferencia',
            'estado_pago' => 'required|in:pagado,pendiente',
            'referencia_pago' => 'nullable|string|max:100',
            'notas' => 'nullable|string|max:255',
            'lineas' => 'required|array|min:1',
            'lineas.*.stock_producto_id' => 'required|exists:stock_productos,id',
            'lineas.*.cantidad' => 'required|integer|min:1',
        ]);

        $hora = $request->hora_venta;
        if (strlen((string) $hora) === 5) {
            $hora .= ':00';
        }

        $ventaData = [
            'nombre_cliente' => $request->nombre_cliente,
            'nombre_turno' => $request->nombre_turno,
            'stock_cancha_id' => (int) $request->stock_cancha_id,
            'fecha_venta' => $request->fecha_venta,
            'hora_venta' => $hora,
            'precio_total' => 0,
            'metodo_pago' => $request->metodo_pago,
            'estado_pago' => $request->estado_pago,
            'fecha_pago' => $request->estado_pago === 'pagado' ? $request->fecha_venta : null,
            'referencia_pago' => $request->referencia_pago,
            'notas' => $request->notas,
        ];

        try {
            $ventaService->crearVenta($ventaData, $request->lineas);
        } catch (\RuntimeException $e) {
            return redirect()->route('admincaja')->with('error', $e->getMessage());
        }

        return redirect()->route('admincaja')->with('success', 'Venta registrada correctamente.');
    }

    public function showVenta(StockVenta $venta)
    {
        $venta->load(['cancha', 'detalles.producto.categoria', 'pagos']);

        return view('bahia_padel.admin.caja.show', compact('venta'));
    }

    public function registrarPago(Request $request, StockVenta $venta, StockVentaService $ventaService)
    {
        $data = $request->validate([
            'metodo_pago' => 'nullable|in:efectivo,transferencia',
            'referencia_pago' => 'nullable|string|max:100',
            'fecha_pago' => 'nullable|date',
            'notas' => 'nullable|string|max:255',
            'caja_fecha' => 'nullable|date_format:Y-m-d|before_or_equal:today',
        ]);

        $resumenFecha = $this->normalizarFechaCaja($data['caja_fecha'] ?? null);

        try {
            $pagoData = \Illuminate\Support\Arr::only($data, ['metodo_pago', 'referencia_pago', 'fecha_pago', 'notas']);
            $ventaService->registrarPago($venta, $pagoData);
        } catch (\RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Pago registrado.',
                'resumen' => $this->cajaResumenAjaxPayload($resumenFecha),
            ]);
        }

        return redirect()->route('admincaja')->with('success', 'Pago registrado.');
    }
}
