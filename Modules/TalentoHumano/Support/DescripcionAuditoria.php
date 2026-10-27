<?php

namespace Modules\TalentoHumano\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\SICA\Entities\Course;
use Modules\TalentoHumano\Entities\Acta;
use Modules\TalentoHumano\Entities\AnalisisHv;
use Modules\TalentoHumano\Entities\Area;
use Modules\TalentoHumano\Entities\Asignacion;
use Modules\TalentoHumano\Entities\Calificacion;
use Modules\TalentoHumano\Entities\CargaInicial;
use Modules\TalentoHumano\Entities\Cargo;
use Modules\TalentoHumano\Entities\CargoActivado;
use Modules\TalentoHumano\Entities\Convocatoria;
use Modules\TalentoHumano\Entities\ConvocatoriaCargo;
use Modules\TalentoHumano\Entities\Cronograma;
use Modules\TalentoHumano\Entities\Cuestionario;
use Modules\TalentoHumano\Entities\Designacion;
use Modules\TalentoHumano\Entities\Empalme;
use Modules\TalentoHumano\Entities\Fase;
use Modules\TalentoHumano\Entities\FaseFicha;
use Modules\TalentoHumano\Entities\InventarioEmpalme;
use Modules\TalentoHumano\Entities\PlantillaActa;
use Modules\TalentoHumano\Entities\Reubicacion;

/**
 * Traduce los registros de auditoría de FOLIUM a frases legibles para la pantalla de Auditoría (HU-002).
 */
class DescripcionAuditoria
{
    /** Secciones del filtro y los tipos de registro que agrupan. */
    public const SECCIONES = [
        'catalogo' => ['Catálogo de cargos', [Cargo::class]],
        'designaciones' => ['Designaciones', [Designacion::class]],
        'asignaciones' => ['Asignaciones', [Asignacion::class, CargaInicial::class, Fase::class]],
        'plantillas' => ['Plantillas de acta', [PlantillaActa::class]],
        'activacion' => ['Activación de cargos', [CargoActivado::class, FaseFicha::class, Area::class, Course::class]],
        'convocatoria' => ['Cronograma y convocatoria', [Cronograma::class, Convocatoria::class]],
        'evaluacion' => ['Postulaciones y entrevistas', [AnalisisHv::class, Cuestionario::class, Calificacion::class]],
        'seleccion' => ['Selección', [ConvocatoriaCargo::class, Reubicacion::class]],
        'empalmes' => ['Empalmes', [Empalme::class, Acta::class, InventarioEmpalme::class]],
        'accesos' => ['Intentos sin permiso', [Auditoria::ACCESO]],
    ];

    /** Acciones que se registran sobre un cargo pero pertenecen a otra sección. */
    public const EVENTOS_DE_SECCION = [
        'activacion' => ['cargo_activado', 'cargo_desactivado', 'cupos_actualizados'],
    ];

    private const ETIQUETAS = [
        'area' => 'Área', 'jefe_inmediato' => 'Jefe inmediato', 'numero_aprendices' => 'Número de aprendices', 'rol_principal' => 'Rol principal',
        'responsabilidades_sig' => 'Responsabilidades SIG', 'documentacion' => 'Documentación', 'requisitos_no_verificables' => 'Requisitos que no se verifican',
        'programas' => 'Tecnólogos compatibles', 'designacion' => 'Designación', 'fecha_retiro' => 'Fecha de retiro', 'motivo_retiro' => 'Motivo del retiro',
        'version' => 'Versión', 'columnas_inventario' => 'Columnas del inventario', 'items_base' => 'Lista base de implementos', 'version_vigente' => 'Versión vigente',
        'fecha_inicio' => 'Fecha de inicio', 'fecha_fin' => 'Fecha de fin', 'numero' => 'Número', 'justificacion' => 'Justificación', 'con_error' => 'Filas con error',
        'peticion' => 'Petición', 'fecha_reunion' => 'Fecha de la reunión', 'compatible' => 'Compatible con su programa', 'documento' => 'Documento', 'unidades' => 'Unidades productivas', 'secciones' => 'Secciones del acta',
    ];

    /** Nombres actuales de los objetos auditados, para describir registros que solo guardan lo que cambió. */
    private array $nombres = [];

    public function __construct(Collection $registros)
    {
        $ids = $registros->groupBy('auditable_type')->map(fn ($r) => $r->pluck('auditable_id')->unique()->all());
        $this->nombres[Cargo::class] = Cargo::withTrashed()->whereIn('id', $ids[Cargo::class] ?? [])->pluck('nombre', 'id')->all();
        $this->nombres[PlantillaActa::class] = PlantillaActa::withTrashed()->whereIn('id', $ids[PlantillaActa::class] ?? [])->pluck('nombre', 'id')->all();
        $this->nombres[Asignacion::class] = DB::table('th_asignaciones as a')->join('people as p', 'p.id', '=', 'a.person_id')->join('th_cargos as c', 'c.id', '=', 'a.cargo_id')
            ->whereIn('a.id', $ids[Asignacion::class] ?? [])
            ->selectRaw("a.id, CONCAT(TRIM(CONCAT_WS(' ', p.first_name, p.first_last_name)), ' (', c.nombre, ')') as nombre")->pluck('nombre', 'id')->all();
    }

    /**
     * Frase con la acción realizada.
     */
    public function accion(object $registro): string
    {
        $o = json_decode($registro->old_values, true) ?: [];
        $n = json_decode($registro->new_values, true) ?: [];
        $nombre = fn () => $this->nombres[$registro->auditable_type][$registro->auditable_id] ?? ($n['nombre'] ?? $o['nombre'] ?? '#' . $registro->auditable_id);
        $nuevo = fn (string $campo) => $n[$campo] ?? '—';
        $anterior = fn (string $campo) => $o[$campo] ?? '—';

        return match ($registro->event) {
            'created' => "Creó el cargo «{$nombre()}»",
            'updated' => "Editó el cargo «{$nombre()}»",
            'deleted' => "Eliminó el cargo «{$nombre()}»",
            'inactivado' => "Inactivó el cargo «{$nombre()}»",
            'reactivado' => "Reactivó el cargo «{$nombre()}»",
            'designacion_asignada' => "Asignó la designación {$nuevo('designacion')} a {$nuevo('instructor')}",
            'designacion_reasignada' => "Reasignó la designación {$nuevo('designacion')} de {$anterior('instructor')} a {$nuevo('instructor')}",
            'designacion_retirada' => "Retiró la designación {$anterior('designacion')} a {$anterior('instructor')}",
            'plantilla_creada' => "Creó la plantilla «{$nuevo('nombre')}» (versión {$nuevo('version')})",
            'plantilla_actualizada' => "Editó la plantilla «{$nuevo('nombre')}» (versión {$nuevo('version')})",
            'plantilla_nueva_version' => "Creó la versión {$nuevo('version')} de la plantilla «{$nuevo('nombre')}»",
            'plantilla_activada' => "Activó la versión {$nuevo('version_vigente')} de la plantilla «{$nombre()}»",
            'fase_registrada' => "Registró la Fase {$nuevo('numero')} como fase en curso",
            'asignacion_creada' => "Creó la asignación de {$nuevo('aprendiz')} ({$nuevo('cargo')}) por carga inicial",
            'asignacion_suspendida' => "Suspendió la asignación de {$nombre()}",
            'asignacion_reactivada' => "Reactivó la asignación de {$nombre()}",
            'rol_gestor_otorgado' => "Otorgó el rol Gestor de Talento Humano a {$nombre()}",
            'rol_gestor_retirado' => "Retiró el rol Gestor de Talento Humano a {$nombre()}",
            'carga_masiva' => "Carga masiva de asignaciones iniciales ({$nuevo('creadas')} creadas, {$nuevo('con_error')} rechazadas)",
            'cargo_activado' => "Activó el cargo «{$nuevo('cargo')}» en la {$nuevo('fase')}",
            'cargo_desactivado' => "Desactivó el cargo «{$nuevo('cargo')}» en la {$nuevo('fase')}",
            'cupos_actualizados' => "Cambió los cupos del cargo «{$nuevo('cargo')}» a {$nuevo('cupos')}",
            'area_activada' => "Activó los {$nuevo('cargos')} cargos del área {$nuevo('area')} en la {$nuevo('fase')}",
            'ficha_agregada' => "Agregó la ficha {$nuevo('ficha')} a la {$nuevo('fase')}",
            'ficha_retirada' => "Retiró la ficha {$anterior('ficha')} de la {$anterior('fase')}",
            'programa_asignado' => "Asignó el programa {$nuevo('programa')} a la ficha {$nuevo('ficha')}",
            'analisis_hv_guardado' => "Analizó la hoja de vida de {$nuevo('aspirante')}",
            'formato_entrevista_guardado' => "Guardó su formato de entrevista ({$nuevo('preguntas')} preguntas)",
            'calificacion_registrada' => "Calificó una entrevista ({$nuevo('puntaje')} puntos, " . str_replace('_', ' ', $nuevo('concepto')) . ')',
            'seleccion_validada' => "Validó la selección del cargo «{$nuevo('cargo')}»",
            'reubicacion_registrada' => "Registró la reubicación de {$nuevo('aprendiz')}: {$nuevo('cargo')}",
            'seleccion_ajustada' => "Ajustó la selección del cargo «{$nuevo('cargo')}»",
            'inventario_agregado' => "Agregó «{$nuevo('item')}» al inventario de un empalme",
            'inventario_verificado' => "Verificó «{$nuevo('item')}» en el inventario de un empalme",
            'acta_firmada' => "Firmó el acta de recibimiento del cargo «{$nuevo('cargo')}»",
            'visto_bueno_empalme' => "Dio el visto bueno al empalme del cargo «{$nuevo('cargo')}»",
            'acceso_denegado' => 'Intentó una acción sin permiso',
            default => Str::ucfirst(str_replace('_', ' ', $registro->event)),
        };
    }

    public function seccion(object $registro): string
    {
        foreach (self::EVENTOS_DE_SECCION as $clave => $eventos) {
            if (in_array($registro->event, $eventos)) {
                return self::SECCIONES[$clave][0];
            }
        }
        foreach (self::SECCIONES as [$titulo, $tipos]) {
            if (in_array($registro->auditable_type, $tipos)) {
                return $titulo;
            }
        }

        return 'Otros';
    }

    /**
     * Campos del registro con su valor anterior y el nuevo: [[campo, anterior, nuevo], …].
     */
    public function cambios(object $registro): array
    {
        $o = json_decode($registro->old_values, true) ?: [];
        $n = json_decode($registro->new_values, true) ?: [];

        return collect(array_unique([...array_keys($o), ...array_keys($n)]))
            ->reject(fn ($campo) => in_array($campo, ['secciones']) && ($o[$campo] ?? null) == ($n[$campo] ?? null))
            ->map(fn ($campo) => [
                self::ETIQUETAS[$campo] ?? Str::ucfirst(str_replace('_', ' ', $campo)),
                self::valor($o[$campo] ?? null),
                self::valor($n[$campo] ?? null),
            ])
            ->values()->all();
    }

    private static function valor(mixed $valor): string
    {
        return match (true) {
            $valor === null, $valor === '', $valor === [] => '—',
            is_bool($valor) => $valor ? 'Sí' : 'No',
            is_array($valor) && array_is_list($valor) && !is_array($valor[0] ?? null) => implode(', ', $valor),
            is_array($valor) => count($valor) . ' elementos',
            default => (string) $valor,
        };
    }
}
