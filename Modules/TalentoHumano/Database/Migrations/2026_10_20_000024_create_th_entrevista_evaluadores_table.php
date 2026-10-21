<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Evaluadores de cada entrevista
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('th_entrevista_evaluadores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entrevista_id')->constrained('th_entrevistas')->cascadeOnDelete();
            $table->foreignId('person_id')->constrained('people')->restrictOnDelete();
            $table->unique(['entrevista_id', 'person_id'], 'th_entr_eval_unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('th_entrevista_evaluadores');
    }
};
