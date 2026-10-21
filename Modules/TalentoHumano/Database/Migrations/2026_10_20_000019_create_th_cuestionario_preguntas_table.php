<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Preguntas de cada cuestionario
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('th_cuestionario_preguntas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuestionario_id')->constrained('th_cuestionarios')->cascadeOnDelete();
            $table->unsignedSmallInteger('orden');
            $table->text('enunciado');
            $table->enum('tipo', ['seleccion', 'escala', 'abierta']);
            $table->json('opciones')->nullable();
            $table->string('imagen', 255)->nullable();
            $table->decimal('puntaje_maximo', 5, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('th_cuestionario_preguntas');
    }
};
