<?php

namespace Modules\TalentoHumano\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\SICA\Entities\Role;
use Modules\TalentoHumano\Entities\Alerta;
use Modules\TalentoHumano\Entities\Asignacion;
use Modules\TalentoHumano\Entities\Cargo;
use Modules\TalentoHumano\Entities\Fase;

/**
 * Reglas compartidas de las asignaciones de cargo (RF-015): carga inicial, suspensión, reactivación y rol Gestor de TH.
 */
class GestionAsignaciones
{
    /**
     * Asignación que ya ocupa un cargo del aprendiz en la fase (RN-029: una por fase).
     */
    public static function ocupada(int $personId, int $faseId): ?Asignacion
    {
        return Asignacion::with('cargo')->where('person_id', $personId)->where('fase_id', $faseId)->whereIn('estado', Asignacion::OCUPAN)->first();
    }

    /**
     * Asignación de carga inicial: entra directamente en "vigente", sin acta ni empalme (RN-032).
     */
    public static function crearCargaInicial(int $personId, Cargo $cargo, Fase $fase, string $fechaInicio, string $justificacion, array $unidades = [], ?int $cargaId = null): Asignacion
    {
        $asignacion = Asignacion::create([
            'person_id' => $personId,
            'cargo_id' => $cargo->id,
            'fase_id' => $fase->id,
            'estado' => 'vigente',
            'origen' => 'carga_inicial',
            'carga_inicial_id' => $cargaId,
            'fecha_inicio' => $fechaInicio,
            'justificacion' => $justificacion,
        ]);
        $asignacion->unidades()->sync($unidades);
        $asignacion->setRelation('cargo', $cargo);

        Auditoria::registrar('asignacion_creada', $asignacion, [], self::instantanea($asignacion) + ['justificacion' => $justificacion]);
        self::sincronizarRolGestor($asignacion);

        return $asignacion;
    }

    /**
     * Suspende o reactiva una asignación con justificación y avisa al otro responsable (RN-031).
     */
    public static function cambiarEstado(Asignacion $asignacion, string $estado, string $justificacion, User $usuario): void
    {
        [$tipo, $fuente] = Auditoria::origen();
        $origen = mb_strtolower($tipo) . ' ' . $fuente;
        $anterior = $asignacion->estado;

        DB::transaction(function () use ($asignacion, $estado, $justificacion, $anterior) {
            $asignacion->update(['estado' => $estado]);
            Auditoria::registrar($estado === 'suspendida' ? 'asignacion_suspendida' : 'asignacion_reactivada', $asignacion,
                ['estado' => $anterior], ['estado' => $estado, 'justificacion' => $justificacion]);
            self::sincronizarRolGestor($asignacion);
        });

        self::avisarAlOtroResponsable($asignacion, $estado, $justificacion, $usuario, $origen);
    }

    /**
     * El rol Gestor de Talento Humano lo tiene quien tenga una asignación vigente a ese cargo (RN-067).
     */
    public static function sincronizarRolGestor(Asignacion $asignacion): void
    {
        if (!$asignacion->esGestorTH()) {
            return;
        }
        $rol = Role::where('slug', 'talentohumano.gestor')->value('id');
        $vigente = Asignacion::where('person_id', $asignacion->person_id)->where('estado', 'vigente')
            ->whereHas('cargo', fn ($q) => $q->where('nombre', Asignacion::CARGO_GESTOR_TH))->exists();
        $origen = ['Sistema', $vigente ? 'La asignación quedó vigente' : 'La asignación dejó de estar vigente'];

        foreach (User::where('person_id', $asignacion->person_id)->get() as $usuario) {
            $tiene = $usuario->roles()->where('roles.id', $rol)->exists();
            if ($vigente && !$tiene) {
                $usuario->roles()->attach($rol);
                Auditoria::registrar('rol_gestor_otorgado', $asignacion, [], ['usuario' => $usuario->nickname, 'rol' => 'Gestor de Talento Humano'], $origen);
            } elseif (!$vigente && $tiene) {
                $usuario->roles()->detach($rol);
                Auditoria::registrar('rol_gestor_retirado', $asignacion, ['usuario' => $usuario->nickname, 'rol' => 'Gestor de Talento Humano'], [], $origen);
            }
        }
    }

    public static function instantanea(Asignacion $asignacion): array
    {
        $asignacion->loadMissing(['cargo', 'fase', 'unidades']);
        $aprendiz = Aprendices::porPersona($asignacion->person_id);

        return [
            'aprendiz' => $aprendiz?->nombre,
            'documento' => $aprendiz?->documento,
            'cargo' => $asignacion->cargo?->nombre,
            'fase' => $asignacion->fase?->nombre(),
            'estado' => $asignacion->estado,
            'origen' => $asignacion->origen,
            'fecha_inicio' => $asignacion->fecha_inicio?->toDateString(),
            'unidades' => $asignacion->unidades->pluck('name')->implode(', '),
        ];
    }

    /**
     * Correo al otro responsable: al Líder de la estrategia si actuó el Administrador, o a los administradores de FOLIUM si actuó el Líder.
     */
    private static function avisarAlOtroResponsable(Asignacion $asignacion, string $estado, string $justificacion, User $usuario, string $origen): void
    {
        $personas = str_starts_with($origen, 'rol')
            ? DB::table('th_designaciones')->where('tipo', 'lider_estrategia')->whereNull('fecha_retiro')->whereNull('deleted_at')->pluck('person_id')
            : User::whereIn('email', AccesoTH::correosAdministradores())->pluck('person_id');
        $correos = User::whereIn('person_id', $personas->reject(fn ($id) => $id === $usuario->person_id))->pluck('email')->filter()->unique();
        $datos = self::instantanea($asignacion);
        $accion = $estado === 'suspendida' ? 'suspendió' : 'reactivó';
        if (!str_starts_with($origen, 'rol')) {
            Alerta::crear($estado === 'suspendida' ? 'asignacion_suspendida' : 'asignacion_reactivada', 'El Instructor Líder ' . $accion . ' una asignación',
                "{$datos['aprendiz']} · {$datos['cargo']}. Justificación: «{$justificacion}». Acción registrada con la designación Líder de la estrategia.",
                route('talentohumano.lider.asignaciones.show', $asignacion));
        }
        $texto = "FOLIUM · Talento Humano\n\n{$usuario->full_name} ({$origen}) {$accion} la asignación de {$datos['aprendiz']} (CC {$datos['documento']}) "
            . "al cargo {$datos['cargo']} en la {$datos['fase']}.\n\nJustificación: {$justificacion}\n\nEste aviso se envía según la regla RN-031.";

        Correo::enviar($correos->all(), "FOLIUM: se {$accion} una asignación de cargo", $texto);
    }
}
