<?php

namespace Modules\TalentoHumano\Database\Seeders;

use Illuminate\Database\Seeder;

class TalentoHumanoDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            TalentoHumanoRolesSeeder::class,
            TalentoHumanoAreasSeeder::class,
            TalentoHumanoCargosSeeder::class,
        ]);
    }
}
