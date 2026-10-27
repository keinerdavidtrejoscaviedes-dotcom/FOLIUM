<?php

namespace Modules\TalentoHumano\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\TalentoHumano\Entities\Fase;
use Modules\TalentoHumano\Entities\Cronograma;
use Modules\TalentoHumano\Entities\CronogramaEtapa;
use Modules\TalentoHumano\Entities\Convocatoria;
use Modules\TalentoHumano\Entities\ConvocatoriaCargo;
use Modules\TalentoHumano\Entities\Cargo;
use Modules\TalentoHumano\Entities\CargoActivado;
use Modules\TalentoHumano\Entities\Postulacion;
use Modules\TalentoHumano\Entities\PostulacionOpcion;
use Modules\TalentoHumano\Entities\PostulacionDocumento;
use Modules\TalentoHumano\Entities\AnalisisHv;
use Modules\SICA\Entities\Person;
use Modules\SICA\Entities\Course;
use Modules\SICA\Entities\Program;
use Modules\SICA\Entities\Municipality;
use App\Models\User;

class TalentoHumanoPostulacionesSeeder extends Seeder
{
    use SoloDemostracion;

    public function run(): void
    {
        if (!$this->puedeCorrer()) {
            return;
        }

        // 1. Fase 36
        $fase = Fase::firstOrCreate(
            ['numero' => 36],
            [
                'fecha_inicio' => '2026-08-01',
                'fecha_fin' => '2026-12-15',
                'estado' => 'seleccion',
            ]
        );

        $personAdminId = User::whereHas('roles', fn ($q) => $q->where('slug', 'talentohumano.instructor'))->value('person_id')
            ?? User::whereHas('roles', fn ($q) => $q->where('slug', 'talentohumano.gestor'))->value('person_id')
            ?? 1;

        // 2. Cronograma de selección
        $cronograma = Cronograma::firstOrCreate(
            ['fase_id' => $fase->id],
            [
                'estado' => 'aprobado',
                'enviado_por' => $personAdminId,
                'aprobado_por' => $personAdminId,
                'fecha_aprobacion' => now(),
            ]
        );

        $etapas = [
            ['Apertura de la convocatoria', '2026-09-01', '2026-09-01'],
            ['Cierre de la convocatoria', '2026-09-12', '2026-09-12'],
            ['Entrega de hojas de vida', '2026-09-01', '2026-09-12'],
            ['Entrega de propuestas de trabajo', '2026-09-01', '2026-09-12'],
            ['Prueba psicotécnica', '2026-09-22', '2026-09-29'],
            ['Entrevistas técnica y psicológica', '2026-10-01', '2026-10-10'],
            ['Validación de la selección y reubicaciones', '2026-10-13', '2026-10-24'],
            ['Publicación del acta de seleccionados', '2026-10-28', '2026-10-28'],
            ['Empalme', '2027-01-11', '2027-01-22'],
        ];

        foreach ($etapas as [$nombre, $inicio, $fin]) {
            CronogramaEtapa::firstOrCreate(
                ['cronograma_id' => $cronograma->id, 'etapa' => $nombre],
                ['fecha_inicio' => $inicio, 'fecha_fin' => $fin]
            );
        }

        // 3. Convocatoria
        $convocatoria = Convocatoria::firstOrCreate(
            ['fase_id' => $fase->id],
            [
                'cronograma_id' => $cronograma->id,
                'titulo' => 'Convocatoria Fase XXXVI - SENA Empresa',
                'fecha_apertura' => '2026-09-01',
                'fecha_cierre' => '2026-09-12',
                'umbral' => 120.00,
                'politica_sin_aptos' => 'mayor_puntaje',
                'estado' => 'cerrada',
                'publicada_por' => $personAdminId,
            ]
        );

        // 4. Activar algunos cargos clave si no están activados
        $nombresCargos = [
            'Gestor de Talento Humano' => 2,
            'Gerente Administrativo' => 1,
            'Gestor Pecuario' => 1,
            'Líder de U. F. Productiva Cacao' => 1,
            'Líder Subsistema Ambiental' => 1,
            'Líder Semillero SEBI' => 1,
            'Equipo Ambiental' => 4,
            'Líder Escuela Cultura de Paz' => 1,
        ];

        $convCargosMap = [];

        foreach ($nombresCargos as $nombre => $cupos) {
            $cargo = Cargo::where('nombre', $nombre)->first();
            if ($cargo) {
                $cargoAct = CargoActivado::firstOrCreate(
                    ['fase_id' => $fase->id, 'cargo_id' => $cargo->id],
                    ['numero_aprendices' => $cupos, 'activado_por' => $personAdminId]
                );

                $convCargo = ConvocatoriaCargo::firstOrCreate(
                    ['convocatoria_id' => $convocatoria->id, 'cargo_activado_id' => $cargoAct->id],
                    ['cupos' => $cupos, 'requisitos' => $cargo->requisitos]
                );

                $convCargosMap[$nombre] = $convCargo;
            }
        }

        // 5. Ficha de muestra (2902114 - Gestión Agroempresarial)
        $programa = Program::where('name', 'like', '%GESTION%AGRO%')->first() ?? Program::first();
        $curso = Course::firstOrCreate(
            ['code' => 2902114],
            [
                'program_id' => $programa->id,
                'start_date' => '2026-01-15',
                'end_date' => '2027-07-15',
                'status' => 'Activo',
            ]
        );

        $municipio = Municipality::first();

        // 6. Aspirantes de muestra de los mockups (INS-01, INS-03, INS-05)
        $aspirantes = [
            [
                'doc' => '9990298441',
                'first' => 'PAULA',
                'last1' => 'DÍAZ',
                'last2' => 'RIVERA',
                'puntaje' => 164.00,
                'opciones' => ['Gestor de Talento Humano', 'Gerente Administrativo', 'Líder Escuela Cultura de Paz'],
                'con_analisis' => true,
                'formacion' => 'Tecnóloga en Gestión Agroempresarial; diplomado en gestión del talento humano.',
                'habilidades' => 'Organización de eventos, manejo de Excel, liderazgo del comité de bienestar de su ficha.',
                'afinidad' => '1. Gestor de TH · Alta | 2. Gerente Administrativo · Media | 3. Escuela Cultura de Paz · Alta',
            ],
            [
                'doc' => '9990298442',
                'first' => 'SERGIO',
                'last1' => 'MEDINA',
                'last2' => 'ORTIZ',
                'puntaje' => 158.00,
                'opciones' => ['Gestor de Talento Humano', 'Gestor Pecuario', 'Líder de U. F. Productiva Cacao'],
                'con_analisis' => false,
            ],
            [
                'doc' => '9990298443',
                'first' => 'DANIEL FELIPE',
                'last1' => 'ORTIZ',
                'last2' => 'SÁNCHEZ',
                'puntaje' => 147.00,
                'opciones' => ['Gestor de Talento Humano', 'Líder de U. F. Productiva Cacao', 'Gestor Pecuario'],
                'con_analisis' => false,
            ],
            [
                'doc' => '9990298444',
                'first' => 'SANTIAGO',
                'last1' => 'ROJAS',
                'last2' => 'POLANÍA',
                'puntaje' => 151.00,
                'opciones' => ['Líder de U. F. Productiva Cacao', 'Gestor Pecuario', 'Equipo Ambiental'],
                'con_analisis' => true,
                'formacion' => 'Tecnólogo en Producción Agrícola con énfasis en cacao.',
                'habilidades' => 'Manejo de podas, injertación y registro de floración.',
                'afinidad' => '1. Líder Cacao · Alta | 2. Gestor Pecuario · Media | 3. Equipo Ambiental · Alta',
            ],
            [
                'doc' => '9990298445',
                'first' => 'JULIANA ANDREA',
                'last1' => 'MORA',
                'last2' => 'PERDOMO',
                'puntaje' => 154.00,
                'opciones' => ['Gerente Administrativo', 'Gestor de Talento Humano', 'Equipo Ambiental'],
                'con_analisis' => false,
            ],
        ];

        foreach ($aspirantes as $asp) {
            $person = Person::firstOrCreate(
                ['document_number' => $asp['doc']],
                [
                    'document_type' => 'Cédula de ciudadanía',
                    'first_name' => $asp['first'],
                    'first_last_name' => $asp['last1'],
                    'second_last_name' => $asp['last2'],
                    'gender' => 'Femenino',
                    'eps_id' => 1,
                    'population_group_id' => 22,
                    'pension_entity_id' => 1,
                    'marital_status' => 'Soltero(a)',
                ]
            );

            $postulacion = Postulacion::updateOrCreate(
                ['convocatoria_id' => $convocatoria->id, 'person_id' => $person->id],
                [
                    'course_id' => $curso->id,
                    'municipality_id' => $municipio->id ?? 1,
                    'vereda_ruta' => 'Ruta Campoalegre - Centro',
                    'estado' => 'postulado',
                    'puntaje_total' => $asp['puntaje'],
                ]
            );

            // Guardar opciones
            foreach ($asp['opciones'] as $index => $cargoNom) {
                if (isset($convCargosMap[$cargoNom])) {
                    PostulacionOpcion::updateOrCreate(
                        ['postulacion_id' => $postulacion->id, 'preferencia' => $index + 1],
                        [
                            'convocatoria_cargo_id' => $convCargosMap[$cargoNom]->id,
                            'propuesta_trabajo' => "Propuesta de trabajo para el cargo de {$cargoNom} enfocada en mejora continua y procesos.",
                        ]
                    );
                }
            }

            // Documentos de muestra
            PostulacionDocumento::firstOrCreate(
                ['postulacion_id' => $postulacion->id, 'tipo' => 'hoja_vida'],
                ['ruta_archivo' => 'docs/hv_muestra.pdf', 'nombre_original' => 'Hoja de vida con soportes.pdf']
            );
            PostulacionDocumento::firstOrCreate(
                ['postulacion_id' => $postulacion->id, 'tipo' => 'soporte'],
                ['ruta_archivo' => 'docs/propuestas.pdf', 'nombre_original' => 'Propuestas de trabajo (3).pdf']
            );
            PostulacionDocumento::firstOrCreate(
                ['postulacion_id' => $postulacion->id, 'tipo' => 'certificado'],
                ['ruta_archivo' => 'docs/curso_virtual.pdf', 'nombre_original' => 'Soporte del curso virtual.pdf']
            );

            // Análisis de hoja de vida (HU-024)
            if ($asp['con_analisis']) {
                AnalisisHv::updateOrCreate(
                    ['postulacion_id' => $postulacion->id],
                    [
                        'evaluador_id' => $personAdminId,
                        'formacion' => $asp['formacion'],
                        'habilidades' => $asp['habilidades'],
                        'afinidad' => $asp['afinidad'],
                        'observaciones' => 'Cumple con los requisitos para habilitar la prueba psicotécnica.',
                    ]
                );
            }
        }
    }
}
