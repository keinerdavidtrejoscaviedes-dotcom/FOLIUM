<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inventario vigente de cada cargo (RN-061)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('th_inventario_cargo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cargo_id')->constrained('th_cargos')->restrictOnDelete();
            $table->foreignId('custodio_cargo_id')->constrained('th_cargos')->restrictOnDelete();
            $table->string('item', 255);
            $table->unsignedInteger('cantidad')->default(1);
            $table->string('estado', 80)->nullable();
            $table->json('datos')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('th_inventario_cargo');
    }
};
