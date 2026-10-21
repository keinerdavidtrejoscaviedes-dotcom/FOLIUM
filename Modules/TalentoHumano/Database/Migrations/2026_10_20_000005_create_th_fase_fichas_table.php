<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fichas seleccionadas para la fase (RF-018)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('th_fase_fichas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fase_id')->constrained('th_fases')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->restrictOnDelete();
            $table->foreignId('registrada_por')->constrained('people')->restrictOnDelete();
            $table->unique(['fase_id', 'course_id'], 'th_fase_ficha_unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('th_fase_fichas');
    }
};
