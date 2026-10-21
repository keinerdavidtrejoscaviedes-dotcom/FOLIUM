<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Formulario de Satisfacción de Empalme
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('th_satisfaccion_empalme', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empalme_id')->constrained('th_empalmes')->cascadeOnDelete();
            $table->foreignId('person_id')->constrained('people')->restrictOnDelete();
            $table->json('respuestas');
            $table->unique(['empalme_id', 'person_id'], 'th_satisf_unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('th_satisfaccion_empalme');
    }
};
