<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alertas de prerrequisitos de una fase: se conservan en su historial con la etapa en que se emitieron (HU-008, HU-013A).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('th_alertas', function (Blueprint $table) {
            $table->foreignId('fase_id')->nullable()->after('id')->constrained('th_fases')->nullOnDelete();
            $table->string('etapa', 60)->nullable()->after('fase_id');
        });
    }

    public function down(): void
    {
        Schema::table('th_alertas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fase_id');
            $table->dropColumn('etapa');
        });
    }
};
