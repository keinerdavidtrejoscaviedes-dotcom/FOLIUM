<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nombre, indicaciones y autor de cada versión de una plantilla de acta (RF-021; HU-008, esc. 3)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('th_plantillas_acta', function (Blueprint $table) {
            $table->string('nombre', 150)->after('area_id');
            $table->text('indicaciones')->nullable()->after('nombre');
            $table->foreignId('creada_por')->nullable()->after('estado')->constrained('people')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('th_plantillas_acta', function (Blueprint $table) {
            $table->dropConstrainedForeignId('creada_por');
            $table->dropColumn(['nombre', 'indicaciones']);
        });
    }
};
