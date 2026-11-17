<?php

namespace Modules\TalentoHumano\Support;

use Illuminate\Support\Facades\DB;
use Modules\TalentoHumano\Entities\Acta;
use Modules\TalentoHumano\Entities\Empalme;
use Modules\TalentoHumano\Entities\Firma;
use Modules\TalentoHumano\Entities\InventarioEmpalme;
use Modules\TalentoHumano\Entities\PlantillaActa;

/**
 * Acta de recibimiento del cargo de un empalme (RF-013, RF-021): plantilla del área, inventario precargado,
 * firmantes requeridos y estado de las firmas. La usan el Aprendiz y la pantalla de empalmes.
 */
class ActaRecibimiento
{
    /** Roles con los que firman los aprendices del empalme (th_firmas.rol_firmante). */
    public const ROL_SALIENTE = 'Aprendiz saliente';
    public const ROL_RECEPTOR = 'Receptor del cargo';

    /** Firmantes que no son aprendices del empalme: cada uno firma desde su propia cuenta (HU-057C). */
    public const ROLES_EXTERNOS = [
        'instructor_area' => 'Instructor del área',
        'jefe_inmediato' => 'Jefe inmediato',
    ];

    /**
     * Plantilla vigente del área del cargo o, si el área no tiene, la plantilla general (RF-021).
     */
    public static function plantilla(Empalme $empalme): ?PlantillaActa
    {
        $areaId = $empalme->cargo?->area_id;
        $vigente = fn (?int $area) => PlantillaActa::deLaMismaArea($area)->where('estado', 'vigente')->latest('version')->first();

        return ($areaId ? $vigente($areaId) : null) ?? $vigente(null);
    }

    /**
     * Firmantes que exige la plantilla (saliente, receptor, instructor_area, jefe_inmediato).
     */
    public static function firmantes(Empalme $empalme): array
    {
        return self::plantilla($empalme)?->firmantes ?: PlantillaActa::BASE['firmantes'];
    }

    /**
     * Genera el acta con la plantilla del área si aún no existe (RN-026).
     */
    public static function asegurar(Empalme $empalme): Acta
    {
        if ($empalme->acta) {
            return $empalme->acta;
        }

        $acta = Acta::create([
            'empalme_id' => $empalme->id,
            'fase_id' => $empalme->fase_id,
            'plantilla_id' => self::plantilla($empalme)?->id,
            'tipo' => 'recibimiento',
            'contenido' => [
                'codigo' => 'ACT-REC-F' . ($empalme->fase?->numero ?? $empalme->fase_id) . '-' . str_pad($empalme->id, 3, '0', STR_PAD_LEFT),
                'cargo' => $empalme->cargo?->nombre,
                'fecha_reunion' => now()->toDateString(),
            ],
            'estado' => 'borrador',
        ]);
        $empalme->setRelation('acta', $acta);

        return $acta;
    }

    /**
     * Precarga el inventario del empalme con el último inventario del cargo o, si no tiene, con los implementos
     * base de la plantilla del área (RN-061). No hace nada si el empalme ya tiene ítems.
     */
    public static function precargarInventario(Empalme $empalme): void
    {
        if ($empalme->inventarios()->exists()) {
            return;
        }

        $items = DB::table('th_inventario_cargo')->where('cargo_id', $empalme->cargo_id)->orderBy('id')->get(['item', 'cantidad', 'estado'])
            ->map(fn ($i) => (array) $i);
        if ($items->isEmpty()) {
            $items = collect(self::plantilla($empalme)?->items_base ?? [])
                ->map(fn ($i) => ['item' => $i['item'], 'cantidad' => $i['cantidad'], 'estado' => null]);
        }

        foreach ($items as $item) {
            InventarioEmpalme::create($item + ['empalme_id' => $empalme->id, 'verificado' => false]);
        }
    }

    /**
     * Personas que deben firmar como aprendices: todos los salientes y los receptores del empalme (RN-059).
     *
     * @return array<int, string> person_id => rol con el que firma
     */
    public static function aprendicesFirmantes(Empalme $empalme): array
    {
        $empalme->loadMissing('participantes.asignacion');
        $requeridos = self::firmantes($empalme);
        $firmantes = [];
        foreach ($empalme->participantes as $participante) {
            $personId = $participante->asignacion?->person_id;
            if ($personId && in_array($participante->papel, $requeridos)) {
                $firmantes[$personId] = $participante->papel === 'saliente' ? self::ROL_SALIENTE : self::ROL_RECEPTOR;
            }
        }

        return $firmantes;
    }

    /**
     * Instructor del área y jefe inmediato que exige la plantilla, con la persona que debe firmar y su firma:
     * [clave => ['rol', 'person_id', 'nombre', 'sugerido', 'firma']]. La persona es la que eligió el Gestor de TH o,
     * si no eligió, la que propone el sistema (responsable de la unidad productiva; quien ocupa el cargo superior).
     */
    public static function firmantesExternos(Empalme $empalme): array
    {
        $firmas = $empalme->acta ? $empalme->acta->firmas()->get() : collect();
        $externos = [];

        foreach (array_intersect(array_keys(self::ROLES_EXTERNOS), self::firmantes($empalme)) as $clave) {
            $elegido = $clave === 'instructor_area' ? $empalme->instructor_area_id : $empalme->jefe_inmediato_id;
            $personId = $elegido ?: ($clave === 'instructor_area' ? self::instructorAreaSugerido($empalme) : self::jefeSugerido($empalme));
            $rol = self::ROLES_EXTERNOS[$clave];

            $externos[$clave] = [
                'rol' => $rol,
                'person_id' => $personId,
                'nombre' => $personId ? self::nombre($personId) : null,
                'sugerido' => !$elegido && $personId,
                'firma' => $personId ? $firmas->first(fn ($f) => $f->person_id == $personId && $f->rol_firmante === $rol) : null,
            ];
        }

        return $externos;
    }

    /**
     * Clave del firmante externo que le corresponde a la persona en el acta (instructor_area o jefe_inmediato), o null.
     */
    public static function rolExterno(Empalme $empalme, ?int $personId): ?string
    {
        if (!$personId) {
            return null;
        }

        return collect(self::firmantesExternos($empalme))->search(fn ($f) => $f['person_id'] == $personId) ?: null;
    }

    /**
     * Responsable de la unidad productiva de los aprendices del empalme (primero la del saliente).
     */
    public static function instructorAreaSugerido(Empalme $empalme): ?int
    {
        $empalme->loadMissing('participantes');
        $asignaciones = $empalme->participantes->sortBy(fn ($p) => $p->papel === 'saliente' ? 0 : 1)->pluck('asignacion_id');
        if ($asignaciones->isEmpty()) {
            return null;
        }

        return DB::table('th_asignacion_unidades as au')
            ->join('productive_units as pu', 'pu.id', '=', 'au.productive_unit_id')
            ->whereIn('au.asignacion_id', $asignaciones)->whereNull('pu.deleted_at')
            ->orderByRaw('FIELD(au.asignacion_id, ' . $asignaciones->map(fn ($id) => (int) $id)->implode(',') . ')')
            ->value('pu.person_id');
    }

    /**
     * Aprendiz que ocupa el cargo superior (jefe inmediato) del cargo en la fase del empalme.
     */
    public static function jefeSugerido(Empalme $empalme): ?int
    {
        $superior = $empalme->cargo?->cargo_superior_id;
        if (!$superior) {
            return null;
        }

        return DB::table('th_asignaciones')->where('fase_id', $empalme->fase_id)->where('cargo_id', $superior)
            ->whereIn('estado', ['asignada', 'vigente'])->orderBy('id')->value('person_id');
    }

    private static function nombre(int $personId): ?string
    {
        return DB::table('people')->where('id', $personId)
            ->selectRaw("TRIM(CONCAT_WS(' ', first_name, first_last_name, second_last_name)) as nombre")->value('nombre');
    }

    /**
     * Actualiza el estado del acta y del empalme después de una firma (HU-057, esc. 3 y 4).
     * Devuelve 'firmada', 'faltan_instructores' o 'pendiente'.
     */
    public static function actualizarEstado(Empalme $empalme, Acta $acta): string
    {
        $firmas = $acta->firmas()->get();
        $aprendices = self::aprendicesFirmantes($empalme);
        $faltanAprendices = array_diff(array_keys($aprendices), $firmas->pluck('person_id')->all());

        // Instructor del área y jefe inmediato: cada uno firma con su propia cuenta; la firma de uno no cuenta por el otro
        $empalme->setRelation('acta', $acta);
        $faltanOtros = collect(self::firmantesExternos($empalme))->filter(fn ($f) => !$f['firma']);

        if (!$faltanAprendices && $faltanOtros->isEmpty()) {
            $acta->update(['estado' => 'firmada', 'fecha_publicacion' => $acta->fecha_publicacion ?? now()]);
            $empalme->update(['estado' => 'firmado']);

            return 'firmada';
        }

        if ($empalme->estado === 'pendiente') {
            $empalme->update(['estado' => 'en_curso']);
        }

        return $faltanAprendices ? 'pendiente' : 'faltan_instructores';
    }

    /**
     * Firma del instructor del área o del jefe inmediato desde su propia cuenta (HU-057C). Devuelve [tipo, mensaje]
     * con tipo 'success', 'info' o 'error'; null si la persona no es firmante externo de esta acta.
     */
    public static function firmarExterno(Empalme $empalme, int $personId): ?array
    {
        $clave = self::rolExterno($empalme, $personId);
        if (!$clave) {
            return null;
        }

        $acta = self::asegurar($empalme);
        $rol = self::ROLES_EXTERNOS[$clave];
        $firmas = $acta->firmas()->get();

        if ($firmas->contains(fn ($f) => $f->person_id == $personId && $f->rol_firmante === $rol)) {
            return ['info', "Ya firmó esta acta como {$rol}."];
        }
        if ($empalme->estado === 'cerrado') {
            return ['error', 'El empalme ya tiene visto bueno: el acta no admite más firmas.'];
        }
        // HU-057C esc. 1: el instructor firma cuando los aprendices ya firmaron
        if (array_diff(array_keys(self::aprendicesFirmantes($empalme)), $firmas->pluck('person_id')->all())) {
            return ['error', 'Primero deben firmar los aprendices del empalme.'];
        }

        Firma::create(['acta_id' => $acta->id, 'person_id' => $personId, 'rol_firmante' => $rol, 'fecha_firma' => now()]);
        Auditoria::registrar('acta_firmada', $acta, [], ['empalme' => $empalme->id, 'cargo' => $empalme->cargo?->nombre, 'rol_firmante' => $rol]);

        return self::actualizarEstado($empalme, $acta) === 'firmada'
            ? ['success', "Firma registrada como {$rol}. El acta quedó firmada por todas las partes."]
            : ['success', "Firma registrada como {$rol}. El acta sigue «Pendiente de firmas» hasta que firmen las demás partes."];
    }

    /**
     * Los aprendices ya firmaron: avisa por correo a cada firmante externo que aún no firma (HU-057B esc. 2).
     */
    public static function avisarFirmantesPendientes(Empalme $empalme): void
    {
        foreach (self::firmantesExternos($empalme) as $firmante) {
            if ($firmante['firma'] || !$firmante['person_id'] || !($correo = Correo::de($firmante['person_id']))) {
                continue;
            }
            Correo::enviar([$correo], "FOLIUM: acta de recibimiento pendiente de su firma ({$empalme->cargo?->nombre})",
                "FOLIUM · Talento Humano\n\nLos aprendices ya firmaron el acta de recibimiento del cargo {$empalme->cargo?->nombre}. "
                . "Falta su firma como {$firmante['rol']}.\n\nFírmela desde su cuenta en " . route('talentohumano.actas-firma.index'));
        }
    }

    /**
     * Actas de recibimiento que la persona debe firmar como instructor del área o jefe inmediato (empalmes abiertos).
     */
    public static function porFirmar(?int $personId)
    {
        if (!$personId) {
            return collect();
        }

        return Empalme::with(['cargo', 'fase', 'acta', 'participantes.asignacion', 'inventarios'])
            ->where('estado', '!=', 'cerrado')
            ->whereHas('fase', fn ($q) => $q->where('estado', '!=', 'cerrada'))
            ->get()
            ->filter(fn ($e) => self::rolExterno($e, $personId))
            ->values();
    }

    /**
     * ¿Diligenciaron el Formulario de Satisfacción todos los aprendices del empalme? (RN-027)
     */
    public static function satisfaccionCompleta(Empalme $empalme): bool
    {
        $personas = array_keys(self::aprendicesFirmantes($empalme));
        $respondieron = DB::table('th_satisfaccion_empalme')->where('empalme_id', $empalme->id)->pluck('person_id')->all();

        return $personas && !array_diff($personas, $respondieron);
    }
}
