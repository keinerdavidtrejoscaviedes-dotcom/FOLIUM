<?php

namespace Modules\TalentoHumano\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\TalentoHumano\Entities\Fase;
use Modules\SICA\Entities\Course;
use Modules\TalentoHumano\Entities\FaseFicha;
use Modules\TalentoHumano\Entities\Cargo;
use Modules\TalentoHumano\Entities\CargoActivado;
use App\Models\User;

class TalentoHumanoFasesSeeder extends Seeder
{
    use SoloDemostracion;

    public function run(): void
    {
        if (!$this->puedeCorrer()) {
            return;
        }

        $fase = Fase::firstOrCreate(
            ['numero' => 36],
            [
                'fecha_inicio' => '2026-08-01',
                'fecha_fin' => '2026-12-15',
                'estado' => 'planeacion',
            ]
        );

        // Asociar un usuario persona por defecto para auditoría si existe
        $personId = User::whereHas('roles', fn ($q) => $q->where('slug', 'talentohumano.instructor'))->value('person_id')
            ?? User::whereHas('roles', fn ($q) => $q->where('slug', 'talentohumano.gestor'))->value('person_id')
            ?? 1;

        // Fichas iniciales de muestra si no hay
        if ($fase->faseFichas()->count() === 0) {
            $fichasMuestra = Course::whereNotNull('program_id')->limit(4)->get();
            foreach ($fichasMuestra as $curso) {
                FaseFicha::firstOrCreate([
                    'fase_id' => $fase->id,
                    'course_id' => $curso->id,
                ], [
                    'registrada_por' => $personId,
                ]);
            }
        }
    }
}
