<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cargos ofrecidos en la convocatoria y sus requisitos (RN-048)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('th_convocatoria_cargos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('convocatoria_id')->constrained('th_convocatorias')->cascadeOnDelete();
            $table->foreignId('cargo_activado_id')->constrained('th_cargos_activados')->restrictOnDelete();
            $table->unsignedTinyInteger('cupos');
            $table->text('requisitos')->nullable();
            $table->json('documentos')->nullable();
            $table->json('programas_extra')->nullable();
            $table->text('justificacion_excepcion')->nullable();
            $table->unique(['convocatoria_id', 'cargo_activado_id'], 'th_conv_cargo_unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('th_convocatoria_cargos');
    }
};
