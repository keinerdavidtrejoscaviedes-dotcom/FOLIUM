<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Datos de una ficha registrados a mano por el Gestor de TH cuando llegaron sin ellos (HU-018): el trimestre en curso
 * o el programa de formación. Mientras no exista la API de SICEFA, completan los datos que se leen del ERP.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('th_fichas_datos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->unique()->constrained('courses')->cascadeOnDelete();
            $table->unsignedTinyInteger('trimestre')->nullable();
            $table->foreignId('program_id')->nullable()->constrained('programs')->nullOnDelete();
            $table->foreignId('registrado_por')->nullable()->constrained('people')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('th_fichas_datos');
    }
};
