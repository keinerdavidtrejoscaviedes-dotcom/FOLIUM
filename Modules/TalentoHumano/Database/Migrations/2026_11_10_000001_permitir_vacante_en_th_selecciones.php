<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un cargo validado sin candidato queda declarado vacante (RN-020): la selección se registra sin postulación.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('th_selecciones', function (Blueprint $table) {
            $table->foreignId('postulacion_id')->nullable()->change();
            $table->decimal('puntaje_total', 6, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('th_selecciones', function (Blueprint $table) {
            $table->decimal('puntaje_total', 6, 2)->nullable(false)->change();
            $table->foreignId('postulacion_id')->nullable(false)->change();
        });
    }
};
