<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Prueba psicotécnica presentada por cada postulante
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('th_aplicaciones_prueba', function (Blueprint $table) {
            $table->id();
            $table->foreignId('postulacion_id')->constrained('th_postulaciones')->cascadeOnDelete();
            $table->foreignId('cuestionario_id')->constrained('th_cuestionarios')->restrictOnDelete();
            $table->enum('estado', ['habilitada', 'en_curso', 'presentada', 'no_evaluado'])->default('habilitada');
            $table->dateTime('fecha_limite');
            $table->decimal('puntaje', 6, 2)->nullable();
            $table->dateTime('fecha_presentacion')->nullable();
            $table->unique(['postulacion_id', 'cuestionario_id'], 'th_aplic_unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('th_aplicaciones_prueba');
    }
};
