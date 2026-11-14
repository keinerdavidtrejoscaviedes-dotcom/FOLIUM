<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\SICA\Entities\Person;

/**
 * Evaluador de una convocatoria (HU-026A, RN-042). Los incluidos por el sistema (motivo 'lider' y 'ficha') se retiran
 * con un motivo y quedan marcados, para que el sistema no los vuelva a incluir; los agregados se quitan de la lista.
 */
class ConvocatoriaEvaluador extends Model
{
    public const TIPOS = ['tecnico' => 'Instructor técnico', 'psicologico' => 'Evaluador psicológico'];

    public const MOTIVOS = ['lider' => 'Instructor Líder', 'ficha' => 'Lidera una ficha de la fase', 'agregado' => 'Agregado'];

    protected $table = 'th_convocatoria_evaluadores';

    protected $fillable = ['convocatoria_id', 'person_id', 'tipo', 'motivo', 'agregado_por', 'retirado_en', 'motivo_retiro', 'retirado_por'];

    protected $casts = ['retirado_en' => 'datetime'];

    public function convocatoria()
    {
        return $this->belongsTo(Convocatoria::class, 'convocatoria_id');
    }

    public function persona()
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    /** Evaluadores que siguen en la convocatoria (no retirados). */
    public function scopeActivos($query)
    {
        return $query->whereNull('retirado_en');
    }

    /** El Instructor Líder y los instructores de las fichas los incluye el sistema. */
    public function incluidoPorSistema(): bool
    {
        return in_array($this->motivo, ['lider', 'ficha']);
    }
}
