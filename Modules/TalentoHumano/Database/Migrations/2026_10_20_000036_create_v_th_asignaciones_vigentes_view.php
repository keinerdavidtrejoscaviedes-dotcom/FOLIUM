<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Vista de solo lectura con las asignaciones vigentes, para los demás módulos del ERP (RN-065).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW v_th_asignaciones_vigentes AS
            SELECT
                a.id                AS asignacion_id,
                p.id                AS person_id,
                p.document_number   AS documento,
                TRIM(CONCAT_WS(' ', p.first_name, p.first_last_name, p.second_last_name)) AS nombre,
                c.id                AS cargo_id,
                c.nombre            AS cargo,
                c.nivel             AS nivel,
                ar.nombre           AS area,
                f.numero            AS fase,
                a.estado            AS estado
            FROM th_asignaciones a
            JOIN people p      ON p.id = a.person_id
            JOIN th_cargos c   ON c.id = a.cargo_id
            JOIN th_fases f    ON f.id = a.fase_id
            LEFT JOIN th_areas ar ON ar.id = c.area_id
            WHERE a.estado = 'vigente'
        ");
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_th_asignaciones_vigentes');
    }
};
