<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StockCajaSalida extends Model
{
    protected $table = 'stock_caja_salidas';

    protected $fillable = [
        'fecha',
        'monto',
        'metodo',
        'descripcion',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
    ];
}

