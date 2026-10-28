<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Datos que registra el Aprendiz: teléfono de contacto de la postulación (RF-005, HU-045)
 * y fecha y participantes de la visita a la unidad productiva del empalme (HU-057).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('th_postulaciones', function (Blueprint $table) {
            $table->string('telefono', 20)->nullable()->after('vereda_ruta');
        });

        Schema::table('th_empalmes', function (Blueprint $table) {
            $table->date('fecha_visita')->nullable()->after('visita_confirmada');
            $table->json('participantes_visita')->nullable()->after('fecha_visita');
        });
    }

    public function down(): void
    {
        Schema::table('th_empalmes', function (Blueprint $table) {
            $table->dropColumn(['fecha_visita', 'participantes_visita']);
        });

        Schema::table('th_postulaciones', function (Blueprint $table) {
            $table->dropColumn('telefono');
        });
    }
};
