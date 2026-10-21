<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Prueba psicotécnica y formatos de entrevista, con versiones (RF-007, RF-009)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('th_cuestionarios', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['prueba_psicotecnica', 'entrevista_tecnica', 'entrevista_psicologica']);
            $table->string('titulo', 255);
            $table->foreignId('autor_id')->constrained('people')->restrictOnDelete();
            $table->unsignedSmallInteger('version')->default(1);
            $table->foreignId('version_anterior_id')->nullable()->constrained('th_cuestionarios')->nullOnDelete();
            $table->enum('estado', ['borrador', 'vigente', 'inactivo'])->default('borrador');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('th_cuestionarios');
    }
};
