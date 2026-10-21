<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fases de SENA Empresa, identificadas por número
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('th_fases', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('numero')->unique();
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->enum('estado', ['planeacion', 'seleccion', 'empalme', 'en_curso', 'cerrada'])->default('planeacion');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('th_fases');
    }
};
