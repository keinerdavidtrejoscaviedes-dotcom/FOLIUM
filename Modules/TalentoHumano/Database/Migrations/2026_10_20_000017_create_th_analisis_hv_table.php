<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Concepto de análisis de hoja de vida (RF-006)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('th_analisis_hv', function (Blueprint $table) {
            $table->id();
            $table->foreignId('postulacion_id')->constrained('th_postulaciones')->cascadeOnDelete();
            $table->foreignId('evaluador_id')->constrained('people')->restrictOnDelete();
            $table->text('formacion')->nullable();
            $table->text('habilidades')->nullable();
            $table->text('afinidad')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('th_analisis_hv');
    }
};
