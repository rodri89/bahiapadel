<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Torneo extends Model
{
    protected $fillable = ['nombre', 'tipo', 'es_torneo_individual', 'fecha_inicio', 'fecha_fin', 'categoria', 'premio_1', 'premio_2', 'descripcion', 'imagen','activo', 'tipo_torneo_formato'];
}
