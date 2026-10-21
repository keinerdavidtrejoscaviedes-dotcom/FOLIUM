<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cargas masivas de asignaciones en la puesta en marcha (RN-068)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('th_cargas_iniciales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fase_id')->constrained('th_fases')->restrictOnDelete();
            $table->string('archivo', 255);
            $table->unsignedInteger('filas_correctas')->default(0);
            $table->unsignedInteger('filas_con_error')->default(0);
            $table->json('errores')->nullable();
            $table->text('justificacion');
            $table->foreignId('cargada_por')->constrained('people')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('th_cargas_iniciales');
    }
};
