<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cronograma del proceso de selección (RF-003)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('th_cronogramas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fase_id')->constrained('th_fases')->restrictOnDelete();
            $table->enum('estado', ['borrador', 'enviado', 'aprobado', 'rechazado'])->default('borrador');
            $table->text('motivo_rechazo')->nullable();
            $table->foreignId('enviado_por')->nullable()->constrained('people')->nullOnDelete();
            $table->foreignId('aprobado_por')->nullable()->constrained('people')->nullOnDelete();
            $table->dateTime('fecha_aprobacion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('th_cronogramas');
    }
};
