<?php

namespace Modules\TalentoHumano\Services;

use Modules\TalentoHumano\Entities\CargoActivado;
use Modules\TalentoHumano\Entities\Convocatoria;
use Modules\TalentoHumano\Entities\Cronograma;
use Modules\TalentoHumano\Entities\Cuestionario;
use Modules\TalentoHumano\Entities\Fase;
use Modules\TalentoHumano\Entities\FaseFicha;
use Modules\TalentoHumano\Support\Auditoria as RegistroAuditoria;

/**
 * Registro de las acciones del módulo (RNF-005).
 *
 * Escribe en la tabla audits del ERP a través de Support\Auditoria, para que las acciones del Gestor de TH
 * y del psicólogo aparezcan en la pantalla de Auditoría del Administrador (HU-002).
 */
class Auditoria
{
    /** Tipo de registro de cada tabla, para agrupar la acción en su sección de la Auditoría. */
    private const TIPOS = [
        'th_fases' => Fase::class,
        'th_fase_fichas' => FaseFicha::class,
        'th_cargos_activados' => CargoActivado::class,
        'th_cronogramas' => Cronograma::class,
        'th_convocatorias' => Convocatoria::class,
        'th_cuestionarios' => Cuestionario::class,
    ];

    public static function registrar(string $accion, string $tabla, ?int $registroId, array $datos = []): void
    {
        RegistroAuditoria::registrarTipo($accion, self::TIPOS[$tabla] ?? $tabla, (int) $registroId, $datos);
    }
}
