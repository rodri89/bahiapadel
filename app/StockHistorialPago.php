<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockHistorialPago extends Model
{
    protected $table = 'stock_historial_pagos';

    public $timestamps = false;

    protected $fillable = [
        'stock_venta_id', 'monto_pagado', 'metodo_pago', 'fecha_pago',
        'referencia_pago', 'usuario_responsable', 'notas', 'created_at',
    ];

    protected $casts = [
        'monto_pagado' => 'decimal:2',
        'fecha_pago' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(StockVenta::class, 'stock_venta_id');
    }
}
