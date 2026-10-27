<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Unidades productivas asociadas a una asignación de cargo (RF-015)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('th_asignacion_unidades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asignacion_id')->constrained('th_asignaciones')->cascadeOnDelete();
            $table->foreignId('productive_unit_id')->constrained('productive_units')->restrictOnDelete();
            $table->unique(['asignacion_id', 'productive_unit_id'], 'th_asig_unidad_unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('th_asignacion_unidades');
    }
};
