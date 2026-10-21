<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Convocatoria de la fase (RF-004)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('th_convocatorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fase_id')->constrained('th_fases')->restrictOnDelete();
            $table->foreignId('cronograma_id')->constrained('th_cronogramas')->restrictOnDelete();
            $table->string('titulo', 255);
            $table->date('fecha_apertura');
            $table->date('fecha_cierre');
            $table->decimal('umbral', 5, 2)->default(0);
            $table->enum('politica_sin_aptos', ['mayor_puntaje', 'declarar_vacante'])->default('mayor_puntaje');
            $table->enum('estado', ['borrador', 'publicada', 'cerrada', 'finalizada'])->default('borrador');
            $table->foreignId('publicada_por')->nullable()->constrained('people')->nullOnDelete();
            $table->unique('fase_id', 'th_conv_fase_unique');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('th_convocatorias');
    }
};
