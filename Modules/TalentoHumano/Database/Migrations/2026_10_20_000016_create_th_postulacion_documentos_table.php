<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hoja de vida, soportes y certificados de la postulación
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('th_postulacion_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('postulacion_id')->constrained('th_postulaciones')->cascadeOnDelete();
            $table->enum('tipo', ['hoja_vida', 'soporte', 'certificado', 'otro']);
            $table->string('ruta_archivo', 255);
            $table->string('nombre_original', 255);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('th_postulacion_documentos');
    }
};
