<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'usuarios';
    public $timestamps = false;

    /**
     * Indica a Laravel que use la columna 'contrasena' para la clave.
     */
    public function getAuthPassword()
    {
        return $this->contrasena;
    }

    protected $fillable = [
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'correo',
        'contrasena',
        'rol_id'
    ];

    protected $hidden = [
        'contrasena',
    ];

    /**
     * IMPORTANTE: Desactivamos el hashing para este proyecto escolar 
     * según tu instrucción. Esto permitirá comparar texto plano.
     */
    protected function casts(): array
    {
        return [];
    }
}
