<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Modules\SICA\Entities\Person;

/**
 * Versión de la plantilla del acta de recibimiento del cargo de un área, o de la plantilla general si no tiene área (RF-021).
 */
class PlantillaActa extends Model
{
    use SoftDeletes;

    public const TIPOS_CAMPO = [
        'texto' => 'Texto corto', 'texto_largo' => 'Texto largo', 'fecha' => 'Fecha', 'numero' => 'Número', 'si_no' => 'Sí / No', 'precargado' => 'Precargado',
    ];

    public const TIPOS_COLUMNA = ['texto' => 'Texto', 'numero' => 'Número', 'lista' => 'Lista de opciones', 'fecha' => 'Fecha'];

    public const FIRMANTES = [
        'saliente' => ['Aprendiz saliente', 'En equipos, firman todos los salientes'],
        'receptor' => ['Receptor del cargo', 'Entrante, líder o gestor que recibe'],
        'instructor_area' => ['Instructor del área', 'Instructor de la unidad productiva'],
        'jefe_inmediato' => ['Jefe inmediato del cargo', 'Gestor o líder del que depende'],
    ];

    /** Estructura con la que arranca una plantilla nueva (el Administrador la ajusta). */
    public const BASE = [
        'secciones' => [
            ['titulo' => 'Datos del cargo', 'fija' => true, 'campos' => [['etiqueta' => 'Cargo', 'tipo' => 'precargado'], ['etiqueta' => 'Área funcional', 'tipo' => 'precargado'], ['etiqueta' => 'Fase saliente y entrante', 'tipo' => 'precargado']]],
            ['titulo' => 'Datos de los aprendices', 'fija' => true, 'campos' => [['etiqueta' => 'Aprendiz saliente', 'tipo' => 'precargado'], ['etiqueta' => 'Aprendiz entrante', 'tipo' => 'precargado'], ['etiqueta' => 'Fecha del empalme', 'tipo' => 'fecha']]],
            ['titulo' => 'Unidad productiva', 'fija' => false, 'campos' => [['etiqueta' => 'Unidad productiva', 'tipo' => 'precargado'], ['etiqueta' => 'Visita realizada', 'tipo' => 'si_no'], ['etiqueta' => 'Estado general de la unidad', 'tipo' => 'texto_largo']]],
            ['titulo' => 'Información que se entrega', 'fija' => false, 'campos' => [['etiqueta' => 'Actividades pendientes', 'tipo' => 'texto_largo'], ['etiqueta' => 'Fechas de entrega de evidencias', 'tipo' => 'texto_largo'], ['etiqueta' => 'Registros y documentos entregados', 'tipo' => 'texto_largo']]],
            ['titulo' => 'Observaciones', 'fija' => false, 'campos' => [['etiqueta' => 'Observaciones generales', 'tipo' => 'texto_largo']]],
        ],
        'columnas_inventario' => [
            ['nombre' => 'Cantidad', 'tipo' => 'numero', 'opciones' => []],
            ['nombre' => 'Estado', 'tipo' => 'lista', 'opciones' => ['Bueno', 'Regular', 'Malo']],
            ['nombre' => 'Observación', 'tipo' => 'texto', 'opciones' => []],
        ],
        'items_base' => [],
        'firmantes' => ['saliente', 'receptor'],
    ];

    protected $table = 'th_plantillas_acta';

    protected $fillable = ['area_id', 'nombre', 'indicaciones', 'version', 'secciones', 'columnas_inventario', 'items_base', 'firmantes', 'estado', 'creada_por'];

    protected $casts = [
        'secciones' => 'array',
        'columnas_inventario' => 'array',
        'items_base' => 'array',
        'firmantes' => 'array',
    ];

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function creadaPor()
    {
        return $this->belongsTo(Person::class, 'creada_por');
    }

    /**
     * Todas las versiones de la plantilla de la misma área (o de la general).
     */
    public function scopeDeLaMismaArea($query, ?int $areaId)
    {
        return $areaId ? $query->where('area_id', $areaId) : $query->whereNull('area_id');
    }

    public function nombreArea(): string
    {
        return $this->area->nombre ?? 'General';
    }

    public function actasGeneradas(): int
    {
        return DB::table('th_actas')->where('plantilla_id', $this->id)->count();
    }

    /**
     * Estructura configurable de la plantilla, en el mismo formato que BASE.
     */
    public function estructura(): array
    {
        return [
            'secciones' => $this->secciones ?? [],
            'columnas_inventario' => $this->columnas_inventario ?? [],
            'items_base' => $this->items_base ?? [],
            'firmantes' => $this->firmantes ?? [],
        ];
    }

    /**
     * Resumen de lo que cambió respecto a otra versión (para el listado de versiones).
     */
    public function cambiosRespectoA(?self $anterior): array
    {
        if (!$anterior) {
            return ['Primera versión'];
        }

        $columnas = fn ($p) => collect($p->columnas_inventario)->pluck('nombre');
        $campos = fn ($p) => collect($p->secciones)->sum(fn ($s) => count($s['campos'] ?? []));
        $implementos = count($this->items_base ?? []) - count($anterior->items_base ?? []);

        $cambios = collect()
            ->merge($columnas($this)->diff($columnas($anterior))->map(fn ($c) => "Columna «{$c}» en el inventario"))
            ->merge($columnas($anterior)->diff($columnas($this))->map(fn ($c) => "Sin la columna «{$c}»"))
            ->merge(collect(array_diff($this->firmantes, $anterior->firmantes))->map(fn ($f) => 'Firma: ' . self::FIRMANTES[$f][0]))
            ->merge(collect(array_diff($anterior->firmantes, $this->firmantes))->map(fn ($f) => 'Ya no firma: ' . self::FIRMANTES[$f][0]));

        if ($implementos) {
            $cambios->push(abs($implementos) . (abs($implementos) === 1 ? ' implemento' : ' implementos') . ($implementos > 0 ? ' más' : ' menos') . ' en la lista base');
        }
        if (count($this->secciones) !== count($anterior->secciones) || $campos($this) !== $campos($anterior)) {
            $cambios->push('Secciones o campos del acta');
        }

        return $cambios->values()->all() ?: ['Ajustes de texto'];
    }
}
