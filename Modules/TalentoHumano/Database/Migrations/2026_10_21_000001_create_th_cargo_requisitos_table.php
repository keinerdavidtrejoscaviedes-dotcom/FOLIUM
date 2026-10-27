<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Requisitos de un cargo que el sistema no puede verificar y que pueden exigir soporte en la postulación (HU-005, esc. 6)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('th_cargo_requisitos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cargo_id')->constrained('th_cargos')->cascadeOnDelete();
            $table->string('descripcion', 255);
            $table->boolean('exige_soporte')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('th_cargo_requisitos');
    }
};
