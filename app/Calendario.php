<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Calendario extends Model
{
    protected $table = 'calendario';

    protected $fillable = ['fecha', 'categoria', 'tipo', 'nombre'];

    protected $casts = [
        'fecha' => 'date',
    ];

    /**
     * Devuelve el label del tipo para mostrar (mixto, damas, libre)
     */
    public function getTipoLabelAttribute()
    {
        $map = [
            'mixto' => 'Mixto',
            'femenino' => 'Damas',
            'masculino' => 'Libre',
        ];
        return $map[strtolower($this->tipo ?? '')] ?? ucfirst($this->tipo ?? '');
    }
}
