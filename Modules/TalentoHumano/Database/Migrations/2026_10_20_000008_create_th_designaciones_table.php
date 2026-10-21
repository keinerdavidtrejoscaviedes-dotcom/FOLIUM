<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Funciones de los instructores (RF-016)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('th_designaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->restrictOnDelete();
            $table->enum('tipo', ['lider_estrategia', 'instructor_th', 'instructor_tecnico']);
            $table->foreignId('program_id')->nullable()->constrained('programs')->nullOnDelete();
            $table->foreignId('fase_id')->nullable()->constrained('th_fases')->nullOnDelete();
            $table->foreignId('asignada_por')->constrained('people')->restrictOnDelete();
            $table->date('fecha_retiro')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('th_designaciones');
    }
};
