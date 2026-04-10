<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DireccionEnvio extends Model
{
    protected $table = 'direcciones_envio';
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $appends = ['direccion_completa'];

    public function getDireccionCompletaAttribute()
    {
        $partes = array_filter([
            $this->calle,
            $this->numero_exterior ? 'No. ' . $this->numero_exterior : null,
            $this->numero_interior ? 'Int. ' . $this->numero_interior : null,
            $this->colonia,
            $this->ciudad,
            $this->estado,
            $this->codigo_postal,
            $this->referencias,
        ]);
        return implode(', ', $partes);
    }
}