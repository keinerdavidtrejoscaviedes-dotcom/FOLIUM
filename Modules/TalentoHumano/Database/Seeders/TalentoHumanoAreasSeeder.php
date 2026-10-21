<?php

namespace Modules\TalentoHumano\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Áreas funcionales de SENA Empresa (agrupan los cargos y las plantillas de acta).
 */
class TalentoHumanoAreasSeeder extends Seeder
{
    public function run(): void
    {
        $areas = [
            'Administrativa' => 'Gerencia administrativa, gestión contable y financiera y gestión del talento humano.',
            'Sistema Integrado de Gestión' => 'Subsistemas de calidad, ambiental y SST, y gestión documental.',
            'Innovación' => 'Asesoría de investigación, desarrollo tecnológico e innovación (I+D+I).',
            'Producción' => 'Gerencia de producción.',
            'Pecuaria' => 'Unidades de formación de producción pecuaria.',
            'Agrícola' => 'Unidades de formación de producción agrícola.',
            'Agroindustrial' => 'Unidades de formación de producción agroindustrial.',
            'Ambiental' => 'Servicios ambientales.',
            'Comercial' => 'Gerencia comercial, mercadeo y ventas.',
        ];

        foreach ($areas as $nombre => $descripcion) {
            DB::table('th_areas')->updateOrInsert(
                ['nombre' => $nombre],
                ['descripcion' => $descripcion, 'deleted_at' => null, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
