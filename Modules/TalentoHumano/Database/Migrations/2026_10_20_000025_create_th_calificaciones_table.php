<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Calificación de cada evaluador a cada aspirante (RF-009)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('th_calificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entrevista_id')->constrained('th_entrevistas')->restrictOnDelete();
            $table->foreignId('postulacion_id')->constrained('th_postulaciones')->restrictOnDelete();
            $table->foreignId('evaluador_id')->constrained('people')->restrictOnDelete();
            $table->foreignId('cuestionario_id')->constrained('th_cuestionarios')->restrictOnDelete();
            $table->decimal('puntaje', 5, 2);
            $table->enum('concepto', ['apto', 'no_apto']);
            $table->text('observaciones')->nullable();
            $table->json('detalle')->nullable();
            $table->unique(['entrevista_id', 'postulacion_id', 'evaluador_id'], 'th_calif_unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('th_calificaciones');
    }
};
