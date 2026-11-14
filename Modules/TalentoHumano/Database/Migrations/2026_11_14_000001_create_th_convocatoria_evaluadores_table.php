<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Evaluadores de la convocatoria (HU-026A, HU-026B, RN-042): el sistema incluye al Instructor Líder y a los instructores
 * que lideran las fichas habilitadas; el Gestor agrega instructores técnicos y psicólogos disponibles.
 * De esta lista salen los evaluadores de cada entrevista (HU-027A, HU-027B).
 *
 * Ajuste del equipo: los incluidos por el sistema también se pueden retirar con un motivo (por ejemplo, si el
 * instructor no está en el centro). Quedan marcados como retirados para que el sistema no los vuelva a incluir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('th_convocatoria_evaluadores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('convocatoria_id')->constrained('th_convocatorias')->cascadeOnDelete();
            $table->foreignId('person_id')->constrained('people')->restrictOnDelete();
            $table->enum('tipo', ['tecnico', 'psicologico']);
            // lider y ficha: incluidos siempre por el sistema; agregado: elegido por el Gestor o el Instructor de TH
            $table->enum('motivo', ['lider', 'ficha', 'agregado']);
            $table->foreignId('agregado_por')->nullable()->constrained('people')->nullOnDelete();
            $table->dateTime('retirado_en')->nullable();
            $table->string('motivo_retiro', 255)->nullable();
            $table->foreignId('retirado_por')->nullable()->constrained('people')->nullOnDelete();
            $table->unique(['convocatoria_id', 'person_id'], 'th_conv_eval_unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('th_convocatoria_evaluadores');
    }
};
