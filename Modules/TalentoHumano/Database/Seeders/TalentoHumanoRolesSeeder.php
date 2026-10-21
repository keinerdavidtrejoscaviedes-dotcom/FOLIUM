<?php

namespace Modules\TalentoHumano\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\SICA\Entities\App;
use Modules\SICA\Entities\Role;

/**
 * Roles de la aplicación Talento Humano (actores del SRS de FOLIUM).
 */
class TalentoHumanoRolesSeeder extends Seeder
{
    public function run(): void
    {
        $app = App::where('url', '/talento-humano')->orWhere('name', 'Talento Humano')->first();

        if (!$app) {
            $this->command?->error('No se encontró la aplicación Talento Humano en la tabla apps (ejecute BloqueAppSeeder de SICA).');
            return;
        }

        $roles = [
            [
                'slug' => 'talentohumano.admin',
                'name' => 'Administrador Talento Humano',
                'description' => 'Instructor líder de SENA Empresa: activa la fase, configura cargos, fichas, cronograma y convocatoria.',
                'description_english' => 'SENA Empresa lead instructor: activates the phase and sets up positions, groups, schedule and call.',
                'full_access' => 'No',
            ],
            [
                'slug' => 'talentohumano.instructor',
                'name' => 'Instructor Talento Humano',
                'description' => 'Instructor de Talento Humano: analiza hojas de vida, coordina entrevistas, valida la selección y el empalme.',
                'description_english' => 'Human Talent instructor: reviews resumes, coordinates interviews, validates selection and handover.',
                'full_access' => 'No',
            ],
            [
                'slug' => 'talentohumano.psicologo',
                'name' => 'Psicólogo Talento Humano',
                'description' => 'Psicólogo: administra la prueba psicotécnica y participa en las entrevistas.',
                'description_english' => 'Psychologist: manages the psychometric test and takes part in interviews.',
                'full_access' => 'No',
            ],
            [
                'slug' => 'talentohumano.gestor',
                'name' => 'Gestor Talento Humano',
                'description' => 'Aprendiz gestor de Talento Humano: apoya la operación del proceso de selección y empalme.',
                'description_english' => 'Human Talent apprentice manager: supports the selection and handover process.',
                'full_access' => 'No',
            ],
            [
                'slug' => 'talentohumano.aprendiz',
                'name' => 'Aprendiz Talento Humano',
                'description' => 'Aprendiz: se postula a los cargos, presenta la prueba y firma las actas de empalme.',
                'description_english' => 'Apprentice: applies for positions, takes the test and signs the handover records.',
                'full_access' => 'No',
            ],
        ];

        foreach ($roles as $data) {
            $role = Role::withTrashed()->where('slug', $data['slug'])->first();

            if ($role?->trashed()) {
                $role->restore();
            }

            Role::updateOrCreate(['slug' => $data['slug']], $data + ['app_id' => $app->id]);
        }
    }
}
