<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Entrevistas programadas (RF-008)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('th_entrevistas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('convocatoria_id')->constrained('th_convocatorias')->restrictOnDelete();
            $table->enum('tipo', ['tecnica', 'psicologica']);
            $table->enum('modalidad', ['individual', 'grupal'])->default('individual');
            $table->dateTime('fecha_hora');
            $table->string('lugar', 150);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('th_entrevistas');
    }
};
