<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Modules\SICA\Entities\Program;

/**
 * Cargo del catálogo (RF-019), tomado del Manual de Roles y Responsabilidades GTH-M-001.
 */
class Cargo extends Model
{
    use SoftDeletes;

    /** Niveles de mayor a menor; no hay cargos por encima del Gerente (HU-004, esc. 3). */
    public const NIVELES = ['Gerente' => 'Gerente', 'Gestor' => 'Gestor', 'Lider' => 'Líder', 'Equipo' => 'Equipo'];

    public const TIPOS_PROGRAMA = [
        'principal' => 'Principal',
        'alterno' => 'Alterno',
        'exclusivo_si_presente' => 'Exclusivo si hay ficha en la fase',
    ];

    /** Jefe inmediato que el sistema asigna a todo Gerente (HU-004, esc. 4). */
    public const JEFE_GERENTE = 'Instructor Líder';

    protected $table = 'th_cargos';

    protected $fillable = [
        'nombre', 'nivel', 'area_id', 'cargo_superior_id', 'jefe_inmediato_texto', 'macroproceso', 'proceso', 'subproceso',
        'numero_aprendices', 'rol_principal', 'actividades', 'responsabilidades_sig', 'documentacion', 'requisitos', 'estado',
    ];

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function superior()
    {
        return $this->belongsTo(self::class, 'cargo_superior_id');
    }

    public function subordinados()
    {
        return $this->hasMany(self::class, 'cargo_superior_id');
    }

    public function programas()
    {
        return $this->belongsToMany(Program::class, 'th_cargo_programas', 'cargo_id', 'program_id')->withPivot('tipo')->withTimestamps();
    }

    public function requisitosAdicionales()
    {
        return $this->hasMany(CargoRequisito::class);
    }

    /**
     * Posición del nivel en la jerarquía: 0 es el más alto (Gerente).
     */
    public static function rango(string $nivel): int
    {
        return array_search($nivel, array_keys(self::NIVELES), true);
    }

    public function nombreJefe(): ?string
    {
        return $this->superior->nombre ?? $this->jefe_inmediato_texto;
    }

    /**
     * Un cargo con historial (activado en alguna fase, asignado o con empalmes) solo puede inactivarse (HU-006, esc. 2).
     */
    public function tieneHistorial(): bool
    {
        return DB::table('th_cargos_activados')->where('cargo_id', $this->id)->exists()
            || DB::table('th_asignaciones')->where('cargo_id', $this->id)->exists()
            || DB::table('th_empalmes')->where('cargo_id', $this->id)->orWhere('cargo_receptor_id', $this->id)->exists();
    }
}
