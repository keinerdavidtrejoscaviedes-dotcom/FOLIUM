<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * HU-004 esc. 4: el soporte de un requisito que el sistema no puede verificar («Exigir soporte») se carga como
 * documento obligatorio de la postulación a ese cargo.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE th_postulacion_documentos MODIFY tipo ENUM('hoja_vida','soporte','certificado','otro','requisito') NOT NULL");

        Schema::table('th_postulacion_documentos', function (Blueprint $table) {
            $table->foreignId('cargo_requisito_id')->nullable()->after('tipo')
                ->constrained('th_cargo_requisitos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('th_postulacion_documentos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cargo_requisito_id');
        });
        DB::table('th_postulacion_documentos')->where('tipo', 'requisito')->update(['tipo' => 'soporte']);
        DB::statement("ALTER TABLE th_postulacion_documentos MODIFY tipo ENUM('hoja_vida','soporte','certificado','otro') NOT NULL");
    }
};
