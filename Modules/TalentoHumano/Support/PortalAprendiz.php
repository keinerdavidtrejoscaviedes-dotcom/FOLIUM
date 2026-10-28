<?php

namespace Modules\TalentoHumano\Support;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\TalentoHumano\Entities\Asignacion;
use Modules\TalentoHumano\Entities\Convocatoria;
use Modules\TalentoHumano\Entities\Cronograma;
use Modules\TalentoHumano\Entities\Empalme;
use Modules\TalentoHumano\Entities\Fase;
use Modules\TalentoHumano\Entities\Postulacion;
use Modules\TalentoHumano\Http\Controllers\CronogramaController;
use Modules\TalentoHumano\Services\FaseService;

/**
 * Situación del Aprendiz que inició sesión en el proceso de la fase en curso (HU-043 a HU-057):
 * su ficha y programa en el ERP, si la ficha está habilitada, el cronograma aprobado, la convocatoria,
 * su postulación, su asignación de cargo y su empalme. Solo lee datos del propio Aprendiz (RN-066).
 */
class PortalAprendiz
{
    public readonly ?object $persona;
    public readonly ?Fase $fase;
    public readonly bool $fichaHabilitada;
    public readonly ?Cronograma $cronograma;
    public readonly ?Convocatoria $convocatoria;
    public readonly ?Postulacion $postulacion;
    public readonly ?Asignacion $asignacion;
    public readonly ?Empalme $empalme;

    public function __construct(public readonly User $user)
    {
        $this->persona = self::persona((int) $user->person_id);
        $this->fase = Fase::actual();

        $this->fichaHabilitada = $this->fase && $this->persona?->course_id
            && DB::table('th_fase_fichas')->where('fase_id', $this->fase->id)->where('course_id', $this->persona->course_id)->exists();

        // Solo el cronograma aprobado se muestra al Aprendiz (HU-043, esc. 4)
        $this->cronograma = $this->fase
            ? Cronograma::with('etapas')->where('fase_id', $this->fase->id)->where('estado', 'aprobado')->latest('id')->first()
            : null;

        // La convocatoria en borrador no es visible (HU-044)
        $this->convocatoria = $this->fase
            ? Convocatoria::where('fase_id', $this->fase->id)->where('estado', '!=', 'borrador')->first()
            : null;

        $this->postulacion = $this->convocatoria
            ? Postulacion::with(['opciones.convocatoriaCargo.cargoActivado.cargo.area', 'documentos', 'municipality'])
                ->where('convocatoria_id', $this->convocatoria->id)->where('person_id', $user->person_id)->first()
            : null;

        // Asignación que da acceso al empalme o al cargo (RN-029: una sola asignada o vigente por fase)
        $this->asignacion = Asignacion::with(['cargo.area', 'fase'])->where('person_id', $user->person_id)
            ->whereIn('estado', ['asignada', 'vigente'])->latest('id')->first();

        $this->empalme = Empalme::with(['cargo.area', 'cargoReceptor', 'fase', 'acta.firmas', 'participantes.asignacion.persona'])
            ->whereHas('participantes.asignacion', fn ($q) => $q->where('person_id', $user->person_id))
            ->orderByRaw("estado = 'cerrado'")->latest('id')->first();
    }

    /**
     * Aprendiz en el ERP: nombre, documento, correo, ficha más reciente y su programa.
     */
    public static function persona(int $personId): ?object
    {
        if (!$personId) {
            return null;
        }

        return DB::table('people as p')
            ->leftJoin('apprentices as a', fn ($j) => $j->on('a.person_id', '=', 'p.id')->whereNull('a.deleted_at')
                ->whereRaw('a.id = (select max(a2.id) from apprentices a2 where a2.person_id = p.id and a2.deleted_at is null)'))
            ->leftJoin('courses as c', 'c.id', '=', 'a.course_id')
            ->leftJoin('programs as pr', 'pr.id', '=', 'c.program_id')
            ->where('p.id', $personId)->whereNull('p.deleted_at')
            ->selectRaw("p.id, p.first_name, TRIM(CONCAT_WS(' ', p.first_name, p.first_last_name, p.second_last_name)) as nombre")
            ->selectRaw('p.document_type, p.document_number as documento, p.telephone1 as telefono')
            ->selectRaw("COALESCE((select u.email from users u where u.person_id = p.id and u.deleted_at is null order by u.id limit 1), p.misena_email, p.sena_email, p.personal_email) as correo")
            ->selectRaw('a.id as aprendiz_id, a.apprentice_status as estado_sicefa, c.id as course_id, c.code as ficha, pr.id as program_id, pr.name as programa')
            ->first();
    }

    /**
     * Etapas del cronograma aprobado con su situación respecto a hoy (hecho, actual o pendiente).
     */
    public function etapas(): Collection
    {
        if (!$this->cronograma) {
            return collect();
        }

        $hoy = today();
        $guardadas = $this->cronograma->etapas->keyBy('etapa');

        return collect(CronogramaController::ETAPAS)->map(function ($etapa, $clave) use ($guardadas, $hoy) {
            $fila = $guardadas[$clave] ?? null;
            $inicio = $fila?->fecha_inicio ? Carbon::parse($fila->fecha_inicio) : null;
            $fin = $fila?->fecha_fin ? Carbon::parse($fila->fecha_fin) : $inicio;

            return (object) [
                'clave' => $clave,
                'nombre' => $etapa['nombre'],
                'inicio' => $inicio,
                'fin' => $fin,
                'rango' => $etapa['rango'] && $fin && !$fin->equalTo($inicio),
                'situacion' => match (true) {
                    !$inicio => 'pendiente',
                    $fin && $fin->lt($hoy) => 'hecho',
                    $inicio->lte($hoy) => 'actual',
                    default => 'pendiente',
                },
            ];
        })->values();
    }

    public function etapa(string $clave): ?object
    {
        return $this->etapas()->firstWhere('clave', $clave);
    }

    /**
     * Fechas de postulación: la etapa "convocatoria" del cronograma o, si no está, las de la convocatoria.
     *
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    public function fechasPostulacion(): array
    {
        $etapa = $this->etapa('convocatoria');

        return [
            $etapa?->inicio ?? $this->convocatoria?->fecha_apertura,
            $etapa?->fin ?? $this->convocatoria?->fecha_cierre,
        ];
    }

    /**
     * Estado de la postulación hoy: 'abierta', 'por_abrir' o 'cerrada' (HU-044 esc. 4, HU-045 esc. 4).
     */
    public function estadoPostulacion(): string
    {
        [$apertura, $cierre] = $this->fechasPostulacion();

        return match (true) {
            !$this->convocatoria || $this->convocatoria->estado !== 'publicada' => 'cerrada',
            $apertura && today()->lt($apertura) => 'por_abrir',
            $cierre && today()->gt($cierre) => 'cerrada',
            default => 'abierta',
        };
    }

    /**
     * Cargos de la convocatoria compatibles con el programa del Aprendiz (HU-044, RN-048): los de sus tecnólogos
     * compatibles y los habilitados por excepción para su programa. 'alterno' indica perfil alterno (HU-044, esc. 3).
     */
    public function cargosCompatibles(): Collection
    {
        if (!$this->convocatoria || !$this->persona?->program_id) {
            return collect();
        }

        $programa = (int) $this->persona->program_id;

        return DB::table('th_convocatoria_cargos as cc')
            ->join('th_cargos_activados as ca', 'ca.id', '=', 'cc.cargo_activado_id')
            ->join('th_cargos as c', 'c.id', '=', 'ca.cargo_id')
            ->leftJoin('th_areas as a', 'a.id', '=', 'c.area_id')
            ->leftJoin('th_cargo_programas as cp', fn ($j) => $j->on('cp.cargo_id', '=', 'c.id')->where('cp.program_id', $programa))
            ->where('cc.convocatoria_id', $this->convocatoria->id)
            ->where(fn ($q) => $q->whereNotNull('cp.id')->orWhereJsonContains('cc.programas_extra', $programa))
            ->select('cc.id', 'cc.cupos', 'cc.requisitos', 'cc.documentos', 'c.id as cargo_id', 'c.nombre', 'c.nivel', 'c.rol_principal', 'a.nombre as area', 'cp.tipo as tipo_programa')
            ->orderBy('a.nombre')->orderBy('c.nombre')
            ->get()
            ->map(function ($cargo) {
                $cargo->documentos = json_decode($cargo->documentos ?? '[]', true) ?: [];
                $cargo->alterno = $cargo->tipo_programa === 'alterno';
                $cargo->excepcion = $cargo->tipo_programa === null;

                return $cargo;
            });
    }

    /**
     * Papel del Aprendiz en su empalme: 'saliente' o 'receptor'.
     */
    public function papel(): ?string
    {
        return $this->empalme?->participantes->first(fn ($p) => $p->asignacion?->person_id == $this->user->person_id)?->papel;
    }
}
