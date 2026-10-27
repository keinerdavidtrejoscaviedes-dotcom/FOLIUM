<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Decisión del comité de reubicación (HU-036): puede ser "sin cargo asignado" (esc. 6), registra la fecha
 * de la reunión (esc. 2) y es una sola por postulación.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('th_reubicaciones', function (Blueprint $table) {
            $table->foreignId('cargo_activado_id')->nullable()->change();
            $table->date('fecha_reunion')->nullable()->after('participantes');
            $table->unique('postulacion_id', 'th_reubic_postulacion_unique');
        });
    }

    public function down(): void
    {
        Schema::table('th_reubicaciones', function (Blueprint $table) {
            $table->dropUnique('th_reubic_postulacion_unique');
            $table->dropColumn('fecha_reunion');
            $table->foreignId('cargo_activado_id')->nullable(false)->change();
        });
    }
};
