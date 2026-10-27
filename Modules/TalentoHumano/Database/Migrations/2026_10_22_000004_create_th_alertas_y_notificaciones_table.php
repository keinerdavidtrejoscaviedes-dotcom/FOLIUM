<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alertas para el Administrador y correos que no se pudieron entregar (HU-011, HU-015, RNF-013)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('th_alertas', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 60);
            $table->string('titulo', 200);
            $table->text('texto')->nullable();
            $table->string('enlace', 255)->nullable();
            $table->timestamp('leida_en')->nullable();
            $table->timestamps();
            $table->index(['leida_en', 'created_at'], 'th_alertas_leida_idx');
        });

        Schema::create('th_notificaciones_fallidas', function (Blueprint $table) {
            $table->id();
            $table->text('destinatarios');
            $table->string('asunto', 255);
            $table->longText('cuerpo');
            $table->text('error');
            $table->unsignedTinyInteger('intentos')->default(0);
            $table->timestamp('entregada_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('th_notificaciones_fallidas');
        Schema::dropIfExists('th_alertas');
    }
};
