<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Jugadore extends Model
{
    protected $fillable = ['nombre','apellido', 'posicion', 'telefono', 'foto','activo'];
}
