<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Salientes y receptores de cada empalme
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('th_empalme_participantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empalme_id')->constrained('th_empalmes')->cascadeOnDelete();
            $table->foreignId('asignacion_id')->constrained('th_asignaciones')->restrictOnDelete();
            $table->enum('papel', ['saliente', 'receptor']);
            $table->unique(['empalme_id', 'asignacion_id'], 'th_emp_part_unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('th_empalme_participantes');
    }
};
