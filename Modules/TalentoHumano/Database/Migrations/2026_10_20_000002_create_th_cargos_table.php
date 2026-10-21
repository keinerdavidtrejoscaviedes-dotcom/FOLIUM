<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo de cargos para aprendices (RF-019)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('th_cargos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150)->unique();
            $table->enum('nivel', ['Gerente', 'Gestor', 'Lider', 'Equipo']);
            $table->foreignId('area_id')->nullable()->constrained('th_areas')->nullOnDelete();
            $table->foreignId('cargo_superior_id')->nullable()->constrained('th_cargos')->nullOnDelete();
            $table->string('jefe_inmediato_texto', 150)->nullable();
            $table->string('macroproceso', 100)->nullable();
            $table->string('proceso', 150)->nullable();
            $table->string('subproceso', 150)->nullable();
            $table->unsignedTinyInteger('numero_aprendices')->default(1);
            $table->text('rol_principal')->nullable();
            $table->text('actividades')->nullable();
            $table->text('responsabilidades_sig')->nullable();
            $table->text('documentacion')->nullable();
            $table->text('requisitos')->nullable();
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('th_cargos');
    }
};
