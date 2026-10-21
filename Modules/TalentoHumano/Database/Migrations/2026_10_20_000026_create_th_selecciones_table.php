<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Propuesta de selección y su validación (RF-010)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('th_selecciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('convocatoria_cargo_id')->constrained('th_convocatoria_cargos')->restrictOnDelete();
            $table->foreignId('postulacion_id')->constrained('th_postulaciones')->restrictOnDelete();
            $table->decimal('puntaje_total', 6, 2);
            $table->enum('estado', ['propuesta', 'validada', 'ajustada'])->default('propuesta');
            $table->text('justificacion')->nullable();
            $table->foreignId('validada_por')->nullable()->constrained('people')->nullOnDelete();
            $table->enum('validada_con', ['lider_estrategia', 'instructor_th'])->nullable();
            $table->dateTime('fecha_validacion')->nullable();
            $table->unique(['convocatoria_cargo_id', 'postulacion_id'], 'th_selec_unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('th_selecciones');
    }
};
