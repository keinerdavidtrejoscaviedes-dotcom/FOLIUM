<?php

namespace Modules\TalentoHumano\Database\Seeders;

/**
 * Seeders con datos de demostración (fases, postulaciones, entrevistas, selecciones y empalmes de prueba).
 * Solo corren en entornos local o de pruebas, nunca sobre la base de datos real, y usan documentos reservados
 * (999…) que no corresponden a personas del ERP.
 */
trait SoloDemostracion
{
    protected function puedeCorrer(): bool
    {
        if (app()->environment(['local', 'testing'])) {
            return true;
        }

        $this->command?->warn(static::class . ': datos de demostración; solo se ejecuta con APP_ENV=local o testing.');

        return false;
    }
}
