<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Actas generadas con plantilla (cronograma, seleccionados, recibimiento)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('th_actas', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['cronograma', 'seleccionados', 'recibimiento']);
            $table->foreignId('fase_id')->constrained('th_fases')->restrictOnDelete();
            $table->foreignId('plantilla_id')->nullable()->constrained('th_plantillas_acta')->nullOnDelete();
            $table->foreignId('empalme_id')->nullable()->constrained('th_empalmes')->nullOnDelete();
            $table->json('contenido');
            $table->enum('estado', ['borrador', 'publicada', 'firmada'])->default('borrador');
            $table->foreignId('publicada_por')->nullable()->constrained('people')->nullOnDelete();
            $table->dateTime('fecha_publicacion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('th_actas');
    }
};
