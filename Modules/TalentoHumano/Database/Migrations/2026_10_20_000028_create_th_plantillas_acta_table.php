<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plantillas de acta por área o general (RF-021); area_id vacío = plantilla general
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('th_plantillas_acta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->nullable()->constrained('th_areas')->nullOnDelete();
            $table->unsignedSmallInteger('version')->default(1);
            $table->json('secciones');
            $table->json('columnas_inventario');
            $table->json('items_base')->nullable();
            $table->json('firmantes');
            $table->enum('estado', ['vigente', 'inactiva'])->default('vigente');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('th_plantillas_acta');
    }
};
