<?php

namespace Modules\TalentoHumano\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class TalentoHumanoServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'TalentoHumano';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'talentohumano';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    protected array $commands = [
        \Modules\TalentoHumano\Console\VencerPruebas::class,
    ];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    /**
     * Define module schedules.
     * 
     * @param $schedule
     */
    protected function configureSchedules(Schedule $schedule): void
    {
        // HU-047 esc. 3: «No evaluado» automático al vencer el plazo de la prueba psicotécnica
        $schedule->command('talentohumano:vencer-pruebas')->hourly();
    }
}
