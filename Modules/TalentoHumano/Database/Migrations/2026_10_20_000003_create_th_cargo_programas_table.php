<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tecnólogos compatibles por cargo: principal, alterno o exclusivo si está presente (RN-037, RN-047, RN-048)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('th_cargo_programas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cargo_id')->constrained('th_cargos')->cascadeOnDelete();
            $table->foreignId('program_id')->constrained('programs')->restrictOnDelete();
            $table->enum('tipo', ['principal', 'alterno', 'exclusivo_si_presente'])->default('principal');
            $table->unique(['cargo_id', 'program_id'], 'th_cargo_prog_unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('th_cargo_programas');
    }
};
