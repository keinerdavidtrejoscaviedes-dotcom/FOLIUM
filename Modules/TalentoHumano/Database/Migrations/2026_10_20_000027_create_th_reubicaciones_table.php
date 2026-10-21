<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Decisión del comité de reubicación (RF-011)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('th_reubicaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('postulacion_id')->constrained('th_postulaciones')->restrictOnDelete();
            $table->foreignId('cargo_activado_id')->constrained('th_cargos_activados')->restrictOnDelete();
            $table->json('participantes');
            $table->text('justificacion')->nullable();
            $table->foreignId('registrada_por')->constrained('people')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('th_reubicaciones');
    }
};
