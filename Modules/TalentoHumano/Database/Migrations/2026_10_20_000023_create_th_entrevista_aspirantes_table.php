<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aspirantes de cada entrevista
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('th_entrevista_aspirantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entrevista_id')->constrained('th_entrevistas')->cascadeOnDelete();
            $table->foreignId('postulacion_id')->constrained('th_postulaciones')->restrictOnDelete();
            $table->unique(['entrevista_id', 'postulacion_id'], 'th_entr_asp_unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('th_entrevista_aspirantes');
    }
};
