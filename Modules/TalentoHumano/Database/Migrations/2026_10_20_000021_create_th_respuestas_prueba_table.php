<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Respuestas de la prueba psicotécnica
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('th_respuestas_prueba', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aplicacion_id')->constrained('th_aplicaciones_prueba')->cascadeOnDelete();
            $table->foreignId('pregunta_id')->constrained('th_cuestionario_preguntas')->restrictOnDelete();
            $table->text('respuesta')->nullable();
            $table->decimal('puntaje', 5, 2)->nullable();
            $table->unique(['aplicacion_id', 'pregunta_id'], 'th_resp_unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('th_respuestas_prueba');
    }
};
