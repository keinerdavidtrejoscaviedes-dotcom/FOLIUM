<?php

namespace Modules\TalentoHumano\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\TalentoHumano\Entities\Fase;
use Modules\TalentoHumano\Entities\Convocatoria;
use Modules\TalentoHumano\Entities\ConvocatoriaCargo;
use Modules\TalentoHumano\Entities\Postulacion;
use Modules\TalentoHumano\Entities\Seleccion;
use Modules\TalentoHumano\Entities\Reubicacion;
use App\Models\User;

class TalentoHumanoSeleccionesSeeder extends Seeder
{
    public function run(): void
    {
        $convocatoria = Convocatoria::with('convocatoriaCargos.cargoActivado.cargo')->first();
        if (!$convocatoria) {
            return;
        }

        $user = User::where('nickname', 'th.instructor')->first();
        $personId = $user->person_id ?? 1;

        // Postulaciones de muestra
        $postulaciones = Postulacion::with('person')->get();
        $paula = $postulaciones->firstWhere('person.first_name', 'PAULA');
        $sergio = $postulaciones->firstWhere('person.first_name', 'SERGIO');
        $santiago = $postulaciones->firstWhere('person.first_name', 'SANTIAGO');

        // 1. Selecciones de muestra
        $gthCargo = $convocatoria->convocatoriaCargos->first(fn ($cc) => $cc->cargoActivado->cargo->nombre === 'Gestor de Talento Humano');
        if ($gthCargo && $paula) {
            Seleccion::firstOrCreate(
                ['convocatoria_cargo_id' => $gthCargo->id, 'postulacion_id' => $paula->id],
                [
                    'puntaje_total' => 164.00,
                    'estado' => 'propuesta',
                ]
            );
        }
        if ($gthCargo && $sergio) {
            Seleccion::firstOrCreate(
                ['convocatoria_cargo_id' => $gthCargo->id, 'postulacion_id' => $sergio->id],
                [
                    'puntaje_total' => 158.00,
                    'estado' => 'propuesta',
                ]
            );
        }

        // Cargos validados
        $gerenteCargo = $convocatoria->convocatoriaCargos->first(fn ($cc) => $cc->cargoActivado->cargo->nombre === 'Gerente Administrativo');
        if ($gerenteCargo && $santiago) {
            Seleccion::firstOrCreate(
                ['convocatoria_cargo_id' => $gerenteCargo->id, 'postulacion_id' => $santiago->id],
                [
                    'puntaje_total' => 171.00,
                    'estado' => 'validada',
                    'validada_por' => $personId,
                    'validada_con' => 'instructor_th',
                    'fecha_validacion' => now()->subDays(2),
                    'justificacion' => 'Validación aprobada según jerarquía de mérito y primera opción.',
                ]
            );
        }
    }
}
