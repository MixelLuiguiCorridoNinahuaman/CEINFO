<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pagos extends Model
{
    use SoftDeletes;

    protected $table = 'pagos';
    protected $primaryKey = 'id_pago';
    public $timestamps = true;

    protected $fillable = [
        'id_mensualidad',
        'fecha_pago',
        'codigo_operacion',
        'entidad_pago',
        'cod_pago',
        'monto',
        'ruta_voucher',
        'observacion',
        'estado',
        'id_usuario_registro',
    ];

    

    protected $casts = [
        'fecha_pago' => 'date',
        'monto' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relaciones
    public function mensualidad()
    {
        return $this->belongsTo(Mensualidad::class, 'id_mensualidad');
    }

    public function usuarioRegistro()
    {
        return $this->belongsTo(User::class, 'id_usuario_registro');
    }
}
