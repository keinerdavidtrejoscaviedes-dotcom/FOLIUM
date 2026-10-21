<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Etapas del cronograma con sus fechas
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('th_cronograma_etapas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cronograma_id')->constrained('th_cronogramas')->cascadeOnDelete();
            $table->string('etapa', 80);
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('th_cronograma_etapas');
    }
};
