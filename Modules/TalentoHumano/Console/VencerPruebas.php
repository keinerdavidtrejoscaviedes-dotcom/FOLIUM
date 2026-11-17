<?php

namespace Modules\TalentoHumano\Console;

use Illuminate\Console\Command;
use Modules\TalentoHumano\Support\PruebaVencida;

/**
 * Marca «No evaluado» a quienes dejaron vencer el plazo de la prueba psicotécnica y avisa al Gestor de TH
 * (HU-047 esc. 3, RN-013). Se programa cada hora; las pantallas del Gestor también lo revisan al abrirse.
 */
class VencerPruebas extends Command
{
    protected $signature = 'talentohumano:vencer-pruebas';

    protected $description = 'Marca como «No evaluado» a los postulantes con el plazo de la prueba psicotécnica vencido';

    public function handle(): int
    {
        $nombres = PruebaVencida::sincronizar();
        $this->info($nombres->isEmpty()
            ? 'No hay postulantes con el plazo de la prueba vencido.'
            : "Quedaron «No evaluado» {$nombres->count()} postulantes: " . $nombres->implode(', '));

        return self::SUCCESS;
    }
}
