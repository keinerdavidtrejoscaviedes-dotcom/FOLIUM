<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quién retiró la designación y por qué (HU-010, esc. 3)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('th_designaciones', function (Blueprint $table) {
            $table->foreignId('retirada_por')->nullable()->after('fecha_retiro')->constrained('people')->nullOnDelete();
            $table->string('motivo_retiro', 255)->nullable()->after('retirada_por');
        });
    }

    public function down(): void
    {
        Schema::table('th_designaciones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('retirada_por');
            $table->dropColumn('motivo_retiro');
        });
    }
};
