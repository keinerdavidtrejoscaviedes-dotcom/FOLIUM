<?php

namespace Modules\TalentoHumano\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\MessageBag;
use Modules\TalentoHumano\Services\Auditoria;
use Modules\TalentoHumano\Services\FaseService;

/**
 * Cronograma del proceso de selección: el Gestor de TH lo elabora y lo envía a aprobación
 * del Instructor Líder (HU-020, RF-003, RN-006, RN-033).
 */
class CronogramaController extends Controller
{
    /** Etapas del cronograma (RF-003). 'rango': tiene fecha inicial y final; si no, una sola fecha. */
    public const ETAPAS = [
        'convocatoria' => ['nombre' => 'Apertura y cierre de la convocatoria', 'rango' => true],
        'hojas_vida' => ['nombre' => 'Entrega de hojas de vida', 'rango' => true],
        'propuestas' => ['nombre' => 'Entrega de propuestas de trabajo', 'rango' => true],
        'prueba_psicotecnica' => ['nombre' => 'Prueba psicotécnica', 'rango' => true],
        'entrevistas' => ['nombre' => 'Entrevistas técnica y psicológica', 'rango' => true],
        'acta_seleccionados' => ['nombre' => 'Publicación del acta de seleccionados', 'rango' => false],
        'empalme' => ['nombre' => 'Empalme', 'rango' => true],
    ];

    public const ESTADOS = [
        'borrador' => 'Borrador',
        'enviado' => 'En aprobación',
        'aprobado' => 'Aprobado',
        'rechazado' => 'Rechazado',
    ];

    public function __construct(private FaseService $fases)
    {
    }

    public function edit(int $fase)
    {
        $fase = $this->fases->buscar($fase);
        $cronograma = $this->fases->cronograma($fase->id);

        $etapas = $cronograma
            ? DB::table('th_cronograma_etapas')->where('cronograma_id', $cronograma->id)->get()->keyBy('etapa')
            : collect();

        return view('talentohumano::fases.cronograma', [
            'fase' => $fase,
            'cronograma' => $cronograma,
            'etapas' => $etapas,
            'editable' => $this->editable($cronograma),
            'resumen' => $this->fases->resumen($fase->id),
        ]);
    }

    public function update(Request $request, int $fase)
    {
        $fase = $this->fases->buscar($fase);
        $cronograma = $this->fases->cronograma($fase->id);
        $enviar = $request->input('accion') === 'enviar';

        if (!$this->editable($cronograma)) {
            return back()->with('error', 'El cronograma ya fue enviado a aprobación; no se puede modificar.');
        }

        [$fechas, $errores] = $this->leerFechas($request);

        if ($enviar && $errores->isEmpty()) {
            $errores = $this->validarEnvio($fase, $fechas);
        }

        if ($errores->isNotEmpty()) {
            return back()->withErrors($errores)->withInput();
        }

        DB::transaction(function () use ($fase, $cronograma, $fechas, $enviar) {
            $datos = [
                'estado' => $enviar ? 'enviado' : 'borrador',
                'enviado_por' => $enviar ? auth()->user()->person_id : null,
                'updated_at' => now(),
            ];

            if ($cronograma) {
                DB::table('th_cronogramas')->where('id', $cronograma->id)->update($datos);
                $id = $cronograma->id;
            } else {
                $id = DB::table('th_cronogramas')->insertGetId($datos + ['fase_id' => $fase->id, 'created_at' => now()]);
            }

            // Solo se guardan las etapas que tienen al menos la fecha inicial (fecha_inicio es obligatoria en la tabla)
            DB::table('th_cronograma_etapas')->where('cronograma_id', $id)->delete();
            DB::table('th_cronograma_etapas')->insert(collect($fechas)
                ->filter(fn ($f) => $f['inicio'])
                ->map(fn ($f, $etapa) => [
                    'cronograma_id' => $id,
                    'etapa' => $etapa,
                    'fecha_inicio' => $f['inicio'],
                    'fecha_fin' => $f['fin'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->values()->all());
        });

        Auditoria::registrar($enviar ? 'Enviar cronograma a aprobación' : 'Guardar cronograma como borrador', 'th_cronogramas', $fase->id, $fechas);

        // Pendiente: aviso por correo al Instructor Líder (IE-007), cuando se defina el envío de notificaciones
        return redirect()->route('talentohumano.fases.cronograma.edit', $fase->id)
            ->with('success', $enviar ? 'Cronograma enviado a aprobación.' : 'Cronograma guardado como borrador.');
    }

    /** Se puede editar mientras no exista, esté en borrador o haya sido rechazado (RF-003). */
    private function editable(?object $cronograma): bool
    {
        return !$cronograma || in_array($cronograma->estado, ['borrador', 'rechazado']);
    }

    /**
     * Lee las fechas del formulario. Para guardar borrador basta con que las fechas escritas sean válidas.
     *
     * @return array{0: array<string, array{inicio: ?string, fin: ?string}>, 1: MessageBag}
     */
    private function leerFechas(Request $request): array
    {
        $fechas = [];
        $errores = new MessageBag();

        foreach (self::ETAPAS as $clave => $etapa) {
            $fechas[$clave] = ['inicio' => null, 'fin' => null];

            foreach (['inicio', 'fin'] as $campo) {
                if ($campo === 'fin' && !$etapa['rango']) {
                    continue;
                }

                $valor = $request->input("etapas.$clave.$campo");
                if (blank($valor)) {
                    continue;
                }

                try {
                    $fecha = Carbon::createFromFormat('!Y-m-d', $valor);
                } catch (\Throwable) {
                    $fecha = null;
                }
                if (!$fecha || $fecha->format('Y-m-d') !== $valor) {
                    $errores->add("etapas.$clave", "{$etapa['nombre']}: la fecha no es válida.");
                    continue;
                }

                $fechas[$clave][$campo] = $valor;
            }

            if ($fechas[$clave]['fin'] && !$fechas[$clave]['inicio']) {
                $errores->add("etapas.$clave", "{$etapa['nombre']}: indique también la fecha inicial.");
            }
        }

        return [$fechas, $errores];
    }

    /**
     * Reglas para 'Enviar a aprobación' (HU-020 escenarios 3 a 8).
     */
    private function validarEnvio(object $fase, array $fechas): MessageBag
    {
        $errores = new MessageBag();
        $hoy = today()->format('Y-m-d');

        // Escenario 6: prerrequisito (RF-002)
        if (!DB::table('th_cargos_activados')->where('fase_id', $fase->id)->exists()) {
            $errores->add('cronograma', 'La activación de cargos es prerrequisito del cronograma.');
        }

        // Escenario 3: todas las etapas con fecha
        $faltantes = [];
        foreach (self::ETAPAS as $clave => $etapa) {
            if (!$fechas[$clave]['inicio'] || ($etapa['rango'] && !$fechas[$clave]['fin'])) {
                $faltantes[] = $etapa['nombre'];
                $errores->add("etapas.$clave", "{$etapa['nombre']}: indique la fecha" . ($etapa['rango'] ? ' inicial y la final.' : '.'));
            }
        }
        if ($faltantes) {
            return $errores;
        }

        // Escenario 5: fechas en el pasado
        foreach (self::ETAPAS as $clave => $etapa) {
            if ($fechas[$clave]['inicio'] < $hoy) {
                $errores->add("etapas.$clave", "{$etapa['nombre']}: las fechas del cronograma no pueden ser anteriores a hoy.");
            }
        }

        // Escenario 4: fechas en orden
        $inicio = fn ($etapa) => $fechas[$etapa]['inicio'];
        $fin = fn ($etapa) => $fechas[$etapa]['fin'] ?? $fechas[$etapa]['inicio'];

        if ($fin('convocatoria') <= $inicio('convocatoria')) {
            $errores->add('etapas.convocatoria', 'El cierre de la convocatoria debe ser posterior a su apertura.');
        }
        foreach (['hojas_vida', 'propuestas', 'prueba_psicotecnica', 'entrevistas', 'empalme'] as $etapa) {
            if ($fin($etapa) < $inicio($etapa)) {
                $errores->add("etapas.$etapa", self::ETAPAS[$etapa]['nombre'] . ': la fecha final no puede ser anterior a la inicial.');
            }
        }
        foreach (['hojas_vida', 'propuestas'] as $etapa) {
            if ($inicio($etapa) < $inicio('convocatoria') || $fin($etapa) > $fin('convocatoria')) {
                $errores->add("etapas.$etapa", self::ETAPAS[$etapa]['nombre'] . ': debe estar dentro de las fechas de la convocatoria.');
            }
        }
        if ($inicio('prueba_psicotecnica') <= $fin('convocatoria')) {
            $errores->add('etapas.prueba_psicotecnica', 'La fecha de la prueba psicotécnica debe ser posterior al cierre de la convocatoria.');
        }
        if ($inicio('entrevistas') < $fin('prueba_psicotecnica')) {
            $errores->add('etapas.entrevistas', 'Las entrevistas deben programarse después de la prueba psicotécnica.');
        }
        if ($inicio('acta_seleccionados') <= $fin('entrevistas')) {
            $errores->add('etapas.acta_seleccionados', 'La publicación del acta debe ser posterior a las entrevistas.');
        }
        if ($inicio('empalme') < $inicio('acta_seleccionados')) {
            $errores->add('etapas.empalme', 'El empalme debe iniciar después de la publicación del acta de seleccionados.');
        }

        // Escenarios 7 y 8: condiciones mínimas del proceso (RN-033)
        $hayLider = DB::table('th_designaciones')
            ->where('tipo', 'lider_estrategia')
            ->whereNull('deleted_at')
            ->where(fn ($q) => $q->whereNull('fecha_retiro')->orWhere('fecha_retiro', '>=', $hoy))
            ->where(fn ($q) => $q->whereNull('fase_id')->orWhere('fase_id', $fase->id))
            ->exists();
        if (!$hayLider) {
            $errores->add('cronograma', 'No hay un Instructor Líder designado para aprobar el cronograma.');
        }

        $hayPsicologo = DB::table('role_user as ru')
            ->join('roles as r', 'r.id', '=', 'ru.role_id')
            ->join('users as u', 'u.id', '=', 'ru.user_id')
            ->where('r.slug', 'talentohumano.psicologo')
            ->whereNull('r.deleted_at')
            ->whereNull('u.deleted_at')
            ->exists();
        if (!$hayPsicologo) {
            $errores->add('cronograma', 'Debe existir al menos un psicólogo activo para iniciar el proceso de selección.');
        }

        return $errores;
    }
}
