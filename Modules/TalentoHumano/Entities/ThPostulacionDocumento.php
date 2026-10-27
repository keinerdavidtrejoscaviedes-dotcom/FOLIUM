<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;

class ThPostulacionDocumento extends Model
{
    protected $table = 'th_postulacion_documentos';

    protected $fillable = [
        'postulacion_id',
        'tipo_documento', // hoja_vida, certificados, propuesta_trabajo, soporte_requisito
        'cargo_id',
        'nombre_archivo',
        'ruta_archivo',
        'tamano_bytes',
    ];

    protected $casts = [
        'tamano_bytes' => 'integer',
    ];

    public function postulacion()
    {
        return $this->belongsTo(ThPostulacion::class, 'postulacion_id');
    }

    public function cargo()
    {
        return $this->belongsTo(ThCargo::class, 'cargo_id');
    }
}
