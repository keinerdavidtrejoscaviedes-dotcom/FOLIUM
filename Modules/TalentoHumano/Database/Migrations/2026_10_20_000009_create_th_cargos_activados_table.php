<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cargos que operan en la fase (RF-001)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('th_cargos_activados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fase_id')->constrained('th_fases')->cascadeOnDelete();
            $table->foreignId('cargo_id')->constrained('th_cargos')->restrictOnDelete();
            $table->unsignedTinyInteger('numero_aprendices');
            $table->foreignId('activado_por')->constrained('people')->restrictOnDelete();
            $table->unique(['fase_id', 'cargo_id'], 'th_cargo_act_unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('th_cargos_activados');
    }
};
