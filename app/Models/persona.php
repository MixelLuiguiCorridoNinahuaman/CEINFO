<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Persona extends Model
{
    protected $table = 'personas';
    protected $primaryKey = 'id_persona';
    public $timestamps = true;

    protected $fillable = [
        'tipo_documento',
        'documento',
        'nombres',
        'apellido_paterno',
        'apellido_materno',
        'celular',
        'correo',
        'direccion',
        'genero',
        'edad',
    ];

    /**
     * Los atributos que deben ocultarse en arrays y JSON.
     * Campos sensibles protegidos por RGPD y Ley de Protección de Datos Personales
     */
    protected $hidden = [
        'documento',      // DNI - Dato personal sensible
        'celular',        // Número de teléfono - Dato de contacto personal
        'correo',         // Email - Dato de contacto personal
        'direccion',      // Dirección - Información de ubicación personal
    ];

    protected $casts = [
        'edad' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function estudiante()
    {
        return $this->hasOne(Estudiante::class, 'id_persona');
    }
}
