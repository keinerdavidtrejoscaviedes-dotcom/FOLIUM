<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Postulación de un aprendiz, con municipio y vereda o ruta (RF-005, RN-070)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('th_postulaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('convocatoria_id')->constrained('th_convocatorias')->restrictOnDelete();
            $table->foreignId('person_id')->constrained('people')->restrictOnDelete();
            $table->foreignId('course_id')->constrained('courses')->restrictOnDelete();
            $table->foreignId('municipality_id')->constrained('municipalities')->restrictOnDelete();
            $table->string('vereda_ruta', 150)->nullable();
            $table->enum('estado', ['postulado', 'no_evaluado', 'seleccionado', 'reubicado', 'no_seleccionado'])->default('postulado');
            $table->decimal('puntaje_total', 6, 2)->nullable();
            $table->unique(['convocatoria_id', 'person_id'], 'th_post_unique');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('th_postulaciones');
    }
};
