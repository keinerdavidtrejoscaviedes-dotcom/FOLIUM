<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\TalentoHumano\Http\Controllers\ConvocatoriaController;

class PostulacionDocumento extends Model
{
    protected $table = 'th_postulacion_documentos';

    protected $fillable = [
        'postulacion_id',
        'tipo',
        'cargo_requisito_id',
        'ruta_archivo',
        'nombre_original',
    ];

    public function postulacion()
    {
        return $this->belongsTo(Postulacion::class, 'postulacion_id');
    }

    /** Requisito no verificable del cargo cuyo soporte es este documento (HU-004 esc. 4). */
    public function requisito()
    {
        return $this->belongsTo(CargoRequisito::class, 'cargo_requisito_id');
    }

    /** Nombre del documento para mostrarlo: el tipo de la convocatoria o «Soporte: [requisito]». */
    public function getEtiquetaAttribute(): string
    {
        return $this->tipo === 'requisito'
            ? 'Soporte: ' . ($this->requisito?->descripcion ?? 'requisito del cargo')
            : (ConvocatoriaController::DOCUMENTOS[$this->tipo] ?? $this->tipo);
    }
}
