<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quién ocupa qué cargo en qué fase (RF-015); nunca se eliminan
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('th_asignaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->restrictOnDelete();
            $table->foreignId('cargo_id')->constrained('th_cargos')->restrictOnDelete();
            $table->foreignId('fase_id')->constrained('th_fases')->restrictOnDelete();
            $table->enum('estado', ['asignada', 'vigente', 'suspendida', 'finalizada'])->default('asignada');
            $table->enum('origen', ['acta', 'reubicacion', 'carga_inicial']);
            $table->foreignId('carga_inicial_id')->nullable()->constrained('th_cargas_iniciales')->nullOnDelete();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->text('justificacion')->nullable();
            $table->index(['person_id', 'fase_id'], 'th_asig_persona_fase_idx');
            $table->index(['fase_id', 'estado'], 'th_asig_fase_estado_idx');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('th_asignaciones');
    }
};
