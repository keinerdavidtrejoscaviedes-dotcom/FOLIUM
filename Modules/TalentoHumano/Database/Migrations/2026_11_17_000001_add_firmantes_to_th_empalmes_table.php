<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Firmantes del acta de recibimiento que no son aprendices del empalme (HU-057C): el instructor del área y el jefe
 * inmediato del cargo firman cada uno desde su cuenta. Si quedan vacíos, el sistema propone al responsable de la
 * unidad productiva y a quien ocupa el cargo superior; el Gestor de TH puede cambiarlos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('th_empalmes', function (Blueprint $table) {
            $table->foreignId('instructor_area_id')->nullable()->after('participantes_visita')->constrained('people')->nullOnDelete();
            $table->foreignId('jefe_inmediato_id')->nullable()->after('instructor_area_id')->constrained('people')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('th_empalmes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('instructor_area_id');
            $table->dropConstrainedForeignId('jefe_inmediato_id');
        });
    }
};
