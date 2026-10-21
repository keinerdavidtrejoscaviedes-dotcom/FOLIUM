<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inventario entregado y verificado en un empalme
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('th_inventario_empalme', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empalme_id')->constrained('th_empalmes')->cascadeOnDelete();
            $table->string('item', 255);
            $table->unsignedInteger('cantidad')->default(1);
            $table->string('estado', 80)->nullable();
            $table->boolean('verificado')->default(false);
            $table->text('novedad')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('th_inventario_empalme');
    }
};
