<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Aplicacion extends Model
{
    protected $table = 'aplicaciones';

    protected $fillable = [
        'nombre',
        'codigo',
        'descripcion',
        'url',
        'color',
        'icono',
        'activo',
        'orden',
    ];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
