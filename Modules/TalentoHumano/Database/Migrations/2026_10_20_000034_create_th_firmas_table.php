<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Firmas de cada acta
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('th_firmas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acta_id')->constrained('th_actas')->cascadeOnDelete();
            $table->foreignId('person_id')->constrained('people')->restrictOnDelete();
            $table->string('rol_firmante', 80);
            $table->dateTime('fecha_firma');
            $table->unique(['acta_id', 'person_id'], 'th_firma_unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('th_firmas');
    }
};
