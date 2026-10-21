<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Las 3 opciones de cargo en orden de preferencia
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('th_postulacion_opciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('postulacion_id')->constrained('th_postulaciones')->cascadeOnDelete();
            $table->foreignId('convocatoria_cargo_id')->constrained('th_convocatoria_cargos')->restrictOnDelete();
            $table->unsignedTinyInteger('preferencia');
            $table->text('propuesta_trabajo');
            $table->unique(['postulacion_id', 'preferencia'], 'th_post_op_pref_unique');
            $table->unique(['postulacion_id', 'convocatoria_cargo_id'], 'th_post_op_cargo_unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('th_postulacion_opciones');
    }
};
