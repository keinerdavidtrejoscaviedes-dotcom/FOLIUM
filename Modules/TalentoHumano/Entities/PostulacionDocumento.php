<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;

class PostulacionDocumento extends Model
{
    protected $table = 'th_postulacion_documentos';

    protected $fillable = [
        'postulacion_id',
        'tipo',
        'ruta_archivo',
        'nombre_original',
    ];

    public function postulacion()
    {
        return $this->belongsTo(Postulacion::class, 'postulacion_id');
    }
}
