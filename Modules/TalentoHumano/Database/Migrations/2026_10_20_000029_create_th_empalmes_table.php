<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Empalme de un cargo (RF-013)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('th_empalmes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fase_id')->constrained('th_fases')->restrictOnDelete();
            $table->foreignId('cargo_id')->constrained('th_cargos')->restrictOnDelete();
            $table->foreignId('cargo_receptor_id')->constrained('th_cargos')->restrictOnDelete();
            $table->enum('tipo', ['individual', 'grupal'])->default('individual');
            $table->enum('estado', ['pendiente', 'en_curso', 'firmado', 'cerrado'])->default('pendiente');
            $table->boolean('visita_confirmada')->default(false);
            $table->foreignId('visto_bueno_por')->nullable()->constrained('people')->nullOnDelete();
            $table->dateTime('fecha_visto_bueno')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('th_empalmes');
    }
};
