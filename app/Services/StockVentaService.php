<?php

namespace App\Services;

use App\StockDetalleVenta;
use App\StockHistorialPago;
use App\StockMovimientoStock;
use App\StockProducto;
use App\StockVenta;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockVentaService
{
    public static function responsable(): ?string
    {
        $u = Auth::user();

        return $u ? (string) ($u->name ?? $u->email ?? 'admin') : 'sistema';
    }

    /**
     * @param  array<string, mixed>  $ventaData
     * @param  array<int, array{stock_producto_id: int, cantidad: int}>  $lineas
     */
    public function crearVenta(array $ventaData, array $lineas): StockVenta
    {
        return DB::transaction(function () use ($ventaData, $lineas) {
            $venta = StockVenta::query()->create($ventaData);
            $total = 0.0;
            $user = self::responsable();

            foreach ($lineas as $line) {
                $pid = (int) $line['stock_producto_id'];
                $qty = (int) $line['cantidad'];
                if ($qty < 1) {
                    continue;
                }

                /** @var StockProducto $producto */
                $producto = StockProducto::query()->lockForUpdate()->findOrFail($pid);
                if ($producto->stock_actual < $qty) {
                    throw new \RuntimeException(
                        "Stock insuficiente para \"{$producto->nombre}\" (disponible: {$producto->stock_actual}, pedido: {$qty})."
                    );
                }
                if (! $producto->activo) {
                    throw new \RuntimeException("El producto \"{$producto->nombre}\" no está activo.");
                }

                $precio = (float) $producto->precio_unitario;
                $subtotal = round($precio * $qty, 2);

                StockDetalleVenta::query()->create([
                    'stock_venta_id' => $venta->id,
                    'stock_producto_id' => $producto->id,
                    'cantidad' => $qty,
                    'precio_unitario' => $precio,
                    'subtotal' => $subtotal,
                    'created_at' => now(),
                ]);

                $anterior = $producto->stock_actual;
                $nueva = $anterior - $qty;
                $producto->stock_actual = $nueva;
                $producto->save();

                StockMovimientoStock::query()->create([
                    'stock_producto_id' => $producto->id,
                    'tipo_movimiento' => 'salida',
                    'cantidad' => $qty,
                    'cantidad_anterior' => $anterior,
                    'cantidad_nueva' => $nueva,
                    'motivo' => 'Venta #'.$venta->id,
                    'usuario_responsable' => $user,
                    'created_at' => now(),
                ]);

                $total += $subtotal;
            }

            if ($total <= 0) {
                throw new \RuntimeException('La venta debe incluir al menos un producto con cantidad válida.');
            }

            $venta->precio_total = round($total, 2);
            $venta->save();

            if ($venta->estado_pago === 'pagado') {
                StockHistorialPago::query()->create([
                    'stock_venta_id' => $venta->id,
                    'monto_pagado' => $venta->precio_total,
                    'metodo_pago' => $venta->metodo_pago,
                    'fecha_pago' => now(),
                    'referencia_pago' => $venta->referencia_pago,
                    'usuario_responsable' => $user,
                    'notas' => 'Pago al registrar la venta',
                    'created_at' => now(),
                ]);
                if (! $venta->fecha_pago) {
                    $venta->fecha_pago = now()->toDateString();
                    $venta->save();
                }
            }

            return $venta->fresh(['detalles.producto', 'cancha']);
        });
    }

    /**
     * Borrador en caja: venta pendiente sin líneas, total 0. El stock se descuenta al agregar cada línea.
     *
     * @param  array<string, mixed>  $ventaData
     */
    public function crearVentaBorrador(array $ventaData): StockVenta
    {
        return DB::transaction(function () use ($ventaData) {
            $hora = $ventaData['hora_venta'] ?? now()->format('H:i:s');
            if (strlen((string) $hora) === 5) {
                $hora .= ':00';
            }

            return StockVenta::query()->create([
                'nombre_cliente' => $ventaData['nombre_cliente'],
                'nombre_turno' => $ventaData['nombre_turno'] ?? null,
                'stock_cancha_id' => (int) $ventaData['stock_cancha_id'],
                'fecha_venta' => $ventaData['fecha_venta'] ?? now()->toDateString(),
                'hora_venta' => $hora,
                'precio_total' => 0,
                'metodo_pago' => 'efectivo',
                'estado_pago' => 'pendiente',
                'fecha_pago' => null,
                'referencia_pago' => null,
                'notas' => $ventaData['notas'] ?? null,
            ]);
        });
    }

    public function actualizarNombreBorrador(StockVenta $venta, string $nombreCliente): void
    {
        DB::transaction(function () use ($venta, $nombreCliente) {
            $venta = StockVenta::query()->lockForUpdate()->findOrFail($venta->id);
            if ($venta->estado_pago !== 'pendiente') {
                throw new \RuntimeException('No se puede editar una venta ya cobrada.');
            }
            $venta->nombre_cliente = $nombreCliente;
            $venta->save();
        });
    }

    public function agregarLineaVenta(StockVenta $venta, int $productoId, int $cantidad): StockVenta
    {
        if ($cantidad < 1) {
            throw new \RuntimeException('La cantidad debe ser al menos 1.');
        }

        return DB::transaction(function () use ($venta, $productoId, $cantidad) {
            $venta = StockVenta::query()->lockForUpdate()->findOrFail($venta->id);
            if ($venta->estado_pago !== 'pendiente') {
                throw new \RuntimeException('La venta ya no admite productos.');
            }

            /** @var StockProducto $producto */
            $producto = StockProducto::query()->lockForUpdate()->findOrFail($productoId);
            if ($producto->stock_actual < $cantidad) {
                throw new \RuntimeException(
                    "Stock insuficiente para \"{$producto->nombre}\" (disponible: {$producto->stock_actual}, pedido: {$cantidad})."
                );
            }
            if (! $producto->activo) {
                throw new \RuntimeException("El producto \"{$producto->nombre}\" no está activo.");
            }

            $user = self::responsable();
            $precio = (float) $producto->precio_unitario;
            $subtotal = round($precio * $cantidad, 2);

            StockDetalleVenta::query()->create([
                'stock_venta_id' => $venta->id,
                'stock_producto_id' => $producto->id,
                'cantidad' => $cantidad,
                'precio_unitario' => $precio,
                'subtotal' => $subtotal,
                'created_at' => now(),
            ]);

            $anterior = $producto->stock_actual;
            $nueva = $anterior - $cantidad;
            $producto->stock_actual = $nueva;
            $producto->save();

            StockMovimientoStock::query()->create([
                'stock_producto_id' => $producto->id,
                'tipo_movimiento' => 'salida',
                'cantidad' => $cantidad,
                'cantidad_anterior' => $anterior,
                'cantidad_nueva' => $nueva,
                'motivo' => 'Venta #'.$venta->id,
                'usuario_responsable' => $user,
                'created_at' => now(),
            ]);

            $venta->precio_total = round((float) $venta->precio_total + $subtotal, 2);
            $venta->save();

            return $venta->fresh(['detalles.producto', 'cancha']);
        });
    }

    public function eliminarLineaVenta(StockVenta $venta, int $detalleId): StockVenta
    {
        return DB::transaction(function () use ($venta, $detalleId) {
            $venta = StockVenta::query()->lockForUpdate()->findOrFail($venta->id);
            if ($venta->estado_pago !== 'pendiente') {
                throw new \RuntimeException('La venta ya no admite cambios.');
            }

            /** @var StockDetalleVenta|null $detalle */
            $detalle = StockDetalleVenta::query()
                ->where('stock_venta_id', $venta->id)
                ->where('id', $detalleId)
                ->lockForUpdate()
                ->first();

            if (! $detalle) {
                throw new \RuntimeException('Línea no encontrada.');
            }

            $user = self::responsable();
            /** @var StockProducto $producto */
            $producto = StockProducto::query()->lockForUpdate()->findOrFail($detalle->stock_producto_id);
            $qty = (int) $detalle->cantidad;
            $subtotal = (float) $detalle->subtotal;

            $anterior = $producto->stock_actual;
            $nueva = $anterior + $qty;
            $producto->stock_actual = $nueva;
            $producto->save();

            StockMovimientoStock::query()->create([
                'stock_producto_id' => $producto->id,
                'tipo_movimiento' => 'entrada',
                'cantidad' => $qty,
                'cantidad_anterior' => $anterior,
                'cantidad_nueva' => $nueva,
                'motivo' => 'Anula línea venta #'.$venta->id,
                'usuario_responsable' => $user,
                'created_at' => now(),
            ]);

            $detalle->delete();
            $venta->precio_total = max(0, round((float) $venta->precio_total - $subtotal, 2));
            $venta->save();

            return $venta->fresh(['detalles.producto', 'cancha']);
        });
    }

    /**
     * Cancela un ticket de caja (borrador): solo ventas pendientes de cobro.
     * Devuelve el stock de todas las líneas y elimina la venta.
     */
    public function cancelarVentaBorrador(StockVenta $venta): void
    {
        DB::transaction(function () use ($venta) {
            $venta = StockVenta::query()->lockForUpdate()->findOrFail($venta->id);
            if ($venta->estado_pago !== 'pendiente') {
                throw new \RuntimeException('Solo se pueden cancelar ventas pendientes de cobro.');
            }

            $user = self::responsable();
            $detalles = StockDetalleVenta::query()
                ->where('stock_venta_id', $venta->id)
                ->lockForUpdate()
                ->get();

            foreach ($detalles as $detalle) {
                /** @var StockProducto $producto */
                $producto = StockProducto::query()->lockForUpdate()->findOrFail($detalle->stock_producto_id);
                $qty = (int) $detalle->cantidad;

                $anterior = $producto->stock_actual;
                $nueva = $anterior + $qty;
                $producto->stock_actual = $nueva;
                $producto->save();

                StockMovimientoStock::query()->create([
                    'stock_producto_id' => $producto->id,
                    'tipo_movimiento' => 'entrada',
                    'cantidad' => $qty,
                    'cantidad_anterior' => $anterior,
                    'cantidad_nueva' => $nueva,
                    'motivo' => 'Cancelación venta #'.$venta->id,
                    'usuario_responsable' => $user,
                    'created_at' => now(),
                ]);

                $detalle->delete();
            }

            $venta->delete();
        });
    }

    public function registrarPago(StockVenta $venta, array $data): void
    {
        DB::transaction(function () use ($venta, $data) {
            $venta = StockVenta::query()->lockForUpdate()->findOrFail($venta->id);
            if ($venta->estado_pago === 'pagado') {
                throw new \RuntimeException('La venta ya está marcada como pagada.');
            }
            if ((float) $venta->precio_total <= 0) {
                throw new \RuntimeException('Agregá al menos un producto antes de cobrar.');
            }

            $venta->estado_pago = 'pagado';
            $venta->fecha_pago = $data['fecha_pago'] ?? now()->toDateString();
            $venta->metodo_pago = $data['metodo_pago'] ?? $venta->metodo_pago;
            if (! empty($data['referencia_pago'])) {
                $venta->referencia_pago = $data['referencia_pago'];
            }
            $venta->save();

            StockHistorialPago::query()->create([
                'stock_venta_id' => $venta->id,
                'monto_pagado' => $venta->precio_total,
                'metodo_pago' => $venta->metodo_pago,
                'fecha_pago' => isset($data['fecha_pago']) ? \Carbon\Carbon::parse($data['fecha_pago']) : now(),
                'referencia_pago' => $venta->referencia_pago,
                'usuario_responsable' => self::responsable(),
                'notas' => $data['notas'] ?? null,
                'created_at' => now(),
            ]);
        });
    }
}
