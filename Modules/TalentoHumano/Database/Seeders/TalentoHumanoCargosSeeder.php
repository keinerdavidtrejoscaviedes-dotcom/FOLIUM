<?php

namespace Modules\TalentoHumano\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Catálogo de cargos (th_cargos) con su jefe inmediato y los tecnólogos compatibles (th_cargo_programas).
 * Los datos están en data/cargos.php.
 */
class TalentoHumanoCargosSeeder extends Seeder
{
    public function run(): void
    {
        $cargos = require __DIR__ . '/data/cargos.php';
        $areas = DB::table('th_areas')->pluck('id', 'nombre');

        DB::transaction(function () use ($cargos, $areas) {
            // 1. Cargos, sin jefe inmediato
            foreach ($cargos as $cargo) {
                DB::table('th_cargos')->updateOrInsert(
                    ['nombre' => $cargo['nombre']],
                    Arr::except($cargo, ['nombre', 'area', 'superior', 'programas_principales', 'programas_alternos']) + [
                        'area_id' => $areas[$cargo['area']] ?? null,
                        'estado' => 'activo',
                        'deleted_at' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            $ids = DB::table('th_cargos')->pluck('id', 'nombre');

            // 2. Jefe inmediato, cuando ya existen todos los cargos
            foreach ($cargos as $cargo) {
                DB::table('th_cargos')->where('id', $ids[$cargo['nombre']])
                    ->update(['cargo_superior_id' => $cargo['superior'] ? $ids[$cargo['superior']] : null]);
            }

            // 3. Tecnólogos compatibles, por código SOFIA (se omiten los programas que no existan en la base de datos)
            $programas = DB::table('programs')->whereNull('deleted_at')->pluck('id', 'sofia_code');
            $faltantes = [];

            foreach ($cargos as $cargo) {
                $tipos = array_fill_keys($cargo['programas_alternos'], 'alterno')
                    + array_fill_keys($cargo['programas_principales'], 'principal');

                foreach ($tipos as $sofia => $tipo) {
                    if (!isset($programas[$sofia])) {
                        $faltantes[$sofia] = true;
                        continue;
                    }

                    DB::table('th_cargo_programas')->updateOrInsert(
                        ['cargo_id' => $ids[$cargo['nombre']], 'program_id' => $programas[$sofia]],
                        ['tipo' => $tipo, 'created_at' => now(), 'updated_at' => now()]
                    );
                }
            }

            if ($faltantes) {
                $this->command?->warn('Programas no encontrados (código SOFIA): ' . implode(', ', array_keys($faltantes)));
            }
        });
    }
}
