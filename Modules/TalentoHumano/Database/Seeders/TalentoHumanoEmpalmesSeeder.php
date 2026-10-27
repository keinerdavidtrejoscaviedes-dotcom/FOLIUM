<?php

namespace Modules\TalentoHumano\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\TalentoHumano\Entities\Fase;
use Modules\TalentoHumano\Entities\Cargo;
use Modules\TalentoHumano\Entities\Asignacion;
use Modules\TalentoHumano\Entities\Empalme;
use Modules\TalentoHumano\Entities\EmpalmeParticipante;
use Modules\TalentoHumano\Entities\InventarioEmpalme;
use Modules\TalentoHumano\Entities\Acta;
use Modules\TalentoHumano\Entities\Firma;
use Modules\SICA\Entities\Person;
use App\Models\User;

class TalentoHumanoEmpalmesSeeder extends Seeder
{
    use SoloDemostracion;

    public function run(): void
    {
        if (!$this->puedeCorrer()) {
            return;
        }

        // 1. Fases
        $fase35 = Fase::firstOrCreate(
            ['numero' => 35],
            [
                'fecha_inicio' => '2026-02-01',
                'fecha_fin' => '2026-07-31',
                'estado' => 'cerrada',
            ]
        );

        $fase36 = Fase::firstOrCreate(
            ['numero' => 36],
            [
                'fecha_inicio' => '2026-08-01',
                'fecha_fin' => '2026-12-15',
                'estado' => 'seleccion',
            ]
        );

        $instructorUser = User::where('nickname', 'th.instructor')->first();
        $instructorPersonId = $instructorUser->person_id ?? 1;

        // 2. Personas salientes de Fase 35
        $salientesData = [
            ['doc' => '9990298501', 'first' => 'ANDREA CAMILA', 'last1' => 'GÓMEZ', 'last2' => 'MORALES', 'cargo' => 'Gestor de Talento Humano'],
            ['doc' => '9990298502', 'first' => 'CARLOS EDUARDO', 'last1' => 'NARVÁEZ', 'last2' => 'SALAS', 'cargo' => 'Gerente Administrativo'],
            ['doc' => '9990298503', 'first' => 'MANUEL FERNANDO', 'last1' => 'DÍAZ', 'last2' => 'CASTILLO', 'cargo' => 'Gestor Pecuario'],
            ['doc' => '9990298504', 'first' => 'JUAN ESTEBAN', 'last1' => 'PERDOMO', 'last2' => 'SILVA', 'cargo' => 'Líder de U. F. Productiva Cacao'],
        ];

        $salientesMap = [];
        foreach ($salientesData as $s) {
            $p = Person::firstOrCreate(
                ['document_number' => $s['doc']],
                [
                    'document_type' => 'Cédula de ciudadanía',
                    'first_name' => $s['first'],
                    'first_last_name' => $s['last1'],
                    'second_last_name' => $s['last2'],
                    'gender' => 'Femenino',
                    'eps_id' => 1,
                    'population_group_id' => 22,
                    'pension_entity_id' => 1,
                    'marital_status' => 'Soltero(a)',
                ]
            );
            $salientesMap[$s['cargo']] = $p;
        }

        // 3. Personas receptoras de Fase 36 (ya existentes en SICA)
        // Por documento de demostración (TalentoHumanoPostulacionesSeeder), nunca por nombre: hay personas reales con esos nombres
        $paula = Person::where('document_number', '9990298441')->first();
        $sergio = Person::where('document_number', '9990298442')->first();
        $daniel = Person::where('document_number', '9990298443')->first();
        $santiago = Person::where('document_number', '9990298444')->first();

        $cargos = [
            'gth' => Cargo::where('nombre', 'Gestor de Talento Humano')->first(),
            'gerente' => Cargo::where('nombre', 'Gerente Administrativo')->first(),
            'pecuario' => Cargo::where('nombre', 'Gestor Pecuario')->first(),
            'cacao' => Cargo::where('nombre', 'Líder de U. F. Productiva Cacao')->first(),
        ];

        // 4. Crear Empalme 1: Gestor de Talento Humano
        if ($cargos['gth'] && isset($salientesMap['Gestor de Talento Humano']) && $paula) {
            $asigSaliente = Asignacion::firstOrCreate(
                ['person_id' => $salientesMap['Gestor de Talento Humano']->id, 'cargo_id' => $cargos['gth']->id, 'fase_id' => $fase35->id],
                ['estado' => 'finalizada', 'origen' => 'acta', 'fecha_inicio' => '2026-02-01', 'fecha_fin' => '2026-07-31']
            );
            $asigReceptor = Asignacion::firstOrCreate(
                ['person_id' => $paula->id, 'cargo_id' => $cargos['gth']->id, 'fase_id' => $fase36->id],
                ['estado' => 'asignada', 'origen' => 'acta', 'fecha_inicio' => '2026-08-01']
            );

            $empalme1 = Empalme::firstOrCreate(
                ['fase_id' => $fase36->id, 'cargo_id' => $cargos['gth']->id],
                [
                    'cargo_receptor_id' => $cargos['gth']->id,
                    'tipo' => 'individual',
                    'estado' => 'en_curso',
                    'visita_confirmada' => true,
                ]
            );

            EmpalmeParticipante::firstOrCreate(['empalme_id' => $empalme1->id, 'asignacion_id' => $asigSaliente->id], ['papel' => 'saliente']);
            EmpalmeParticipante::firstOrCreate(['empalme_id' => $empalme1->id, 'asignacion_id' => $asigReceptor->id], ['papel' => 'receptor']);

            // Inventario
            $items1 = [
                ['item' => 'Carpeta física de hojas de vida y actas de selección Fase XXXV', 'cantidad' => 2, 'estado' => 'Bueno', 'verificado' => true],
                ['item' => 'Libro foliado de actas de comité de talento humano', 'cantidad' => 1, 'estado' => 'Bueno', 'verificado' => true],
                ['item' => 'Sello institucional de Talento Humano SENA Empresa', 'cantidad' => 1, 'estado' => 'Regular', 'verificado' => false, 'novedad' => 'Requiere recambio de almohadilla de tinta'],
                ['item' => 'Acceso y permisos a la carpeta en la nube de Google Drive de TH', 'cantidad' => 1, 'estado' => 'Excelente', 'verificado' => true],
            ];
            foreach ($items1 as $it) {
                InventarioEmpalme::firstOrCreate(['empalme_id' => $empalme1->id, 'item' => $it['item']], $it);
            }

            // Acta de recibimiento
            $acta1 = Acta::firstOrCreate(
                ['empalme_id' => $empalme1->id],
                [
                    'tipo' => 'recibimiento',
                    'fase_id' => $fase36->id,
                    'contenido' => [
                        'codigo' => 'ACT-REC-F36-001',
                        'cargo' => 'Gestor de Talento Humano',
                        'saliente' => 'Andrea Camila Gómez Morales',
                        'receptor' => 'Paula Díaz Rivera',
                        'fecha_reunion' => '2026-10-22',
                        'observaciones' => 'Se verificó el 75% del inventario físico y documental del área de Talento Humano.',
                    ],
                    'estado' => 'borrador',
                ]
            );

            // Firmas del acta 1
            Firma::firstOrCreate(
                ['acta_id' => $acta1->id, 'person_id' => $salientesMap['Gestor de Talento Humano']->id],
                ['rol_firmante' => 'Aprendiz Saliente', 'fecha_firma' => now()->subDays(2)]
            );
            Firma::firstOrCreate(
                ['acta_id' => $acta1->id, 'person_id' => $paula->id],
                ['rol_firmante' => 'Aprendiz Receptor', 'fecha_firma' => now()->subDay()]
            );
        }

        // 5. Crear Empalme 2: Gerente Administrativo (Firmado completo)
        if ($cargos['gerente'] && isset($salientesMap['Gerente Administrativo']) && $santiago) {
            $asigSaliente2 = Asignacion::firstOrCreate(
                ['person_id' => $salientesMap['Gerente Administrativo']->id, 'cargo_id' => $cargos['gerente']->id, 'fase_id' => $fase35->id],
                ['estado' => 'finalizada', 'origen' => 'acta', 'fecha_inicio' => '2026-02-01', 'fecha_fin' => '2026-07-31']
            );
            $asigReceptor2 = Asignacion::firstOrCreate(
                ['person_id' => $santiago->id, 'cargo_id' => $cargos['gerente']->id, 'fase_id' => $fase36->id],
                ['estado' => 'vigente', 'origen' => 'acta', 'fecha_inicio' => '2026-08-01']
            );

            $empalme2 = Empalme::firstOrCreate(
                ['fase_id' => $fase36->id, 'cargo_id' => $cargos['gerente']->id],
                [
                    'cargo_receptor_id' => $cargos['gerente']->id,
                    'tipo' => 'individual',
                    'estado' => 'firmado',
                    'visita_confirmada' => true,
                    'visto_bueno_por' => $instructorPersonId,
                    'fecha_visto_bueno' => now()->subDays(3),
                ]
            );

            EmpalmeParticipante::firstOrCreate(['empalme_id' => $empalme2->id, 'asignacion_id' => $asigSaliente2->id], ['papel' => 'saliente']);
            EmpalmeParticipante::firstOrCreate(['empalme_id' => $empalme2->id, 'asignacion_id' => $asigReceptor2->id], ['papel' => 'receptor']);

            $items2 = [
                ['item' => 'Llaves de la oficina administrativa central', 'cantidad' => 2, 'estado' => 'Excelente', 'verificado' => true],
                ['item' => 'Equipo de cómputo portátil Lenovo institucional con cargador', 'cantidad' => 1, 'estado' => 'Bueno', 'verificado' => true],
                ['item' => 'Libro de inventarios generales de SENA Empresa', 'cantidad' => 1, 'estado' => 'Bueno', 'verificado' => true],
                ['item' => 'Sellos secos y húmedos de Gerencia General', 'cantidad' => 2, 'estado' => 'Excelente', 'verificado' => true],
            ];
            foreach ($items2 as $it) {
                InventarioEmpalme::firstOrCreate(['empalme_id' => $empalme2->id, 'item' => $it['item']], $it);
            }

            $acta2 = Acta::firstOrCreate(
                ['empalme_id' => $empalme2->id],
                [
                    'tipo' => 'recibimiento',
                    'fase_id' => $fase36->id,
                    'contenido' => [
                        'codigo' => 'ACT-REC-F36-002',
                        'cargo' => 'Gerente Administrativo',
                        'saliente' => 'Carlos Eduardo Narváez Salas',
                        'receptor' => 'Santiago Rojas Polanía',
                        'fecha_reunion' => '2026-10-20',
                        'observaciones' => 'Entrega 100% conforme. Inventario y responsabilidades recibidas a satisfacción.',
                    ],
                    'estado' => 'firmada',
                    'publicada_por' => $instructorPersonId,
                    'fecha_publicacion' => now()->subDays(3),
                ]
            );

            Firma::firstOrCreate(['acta_id' => $acta2->id, 'person_id' => $salientesMap['Gerente Administrativo']->id], ['rol_firmante' => 'Aprendiz Saliente', 'fecha_firma' => now()->subDays(3)]);
            Firma::firstOrCreate(['acta_id' => $acta2->id, 'person_id' => $santiago->id], ['rol_firmante' => 'Aprendiz Receptor', 'fecha_firma' => now()->subDays(3)]);
            Firma::firstOrCreate(['acta_id' => $acta2->id, 'person_id' => $instructorPersonId], ['rol_firmante' => 'Instructor de Talento Humano', 'fecha_firma' => now()->subDays(3)]);
        }

        // 6. Crear Empalme 3: Gestor Pecuario (Pendiente)
        if ($cargos['pecuario'] && isset($salientesMap['Gestor Pecuario']) && $daniel) {
            $asigSaliente3 = Asignacion::firstOrCreate(
                ['person_id' => $salientesMap['Gestor Pecuario']->id, 'cargo_id' => $cargos['pecuario']->id, 'fase_id' => $fase35->id],
                ['estado' => 'finalizada', 'origen' => 'acta', 'fecha_inicio' => '2026-02-01', 'fecha_fin' => '2026-07-31']
            );
            $asigReceptor3 = Asignacion::firstOrCreate(
                ['person_id' => $daniel->id, 'cargo_id' => $cargos['pecuario']->id, 'fase_id' => $fase36->id],
                ['estado' => 'asignada', 'origen' => 'acta', 'fecha_inicio' => '2026-08-01']
            );

            $empalme3 = Empalme::firstOrCreate(
                ['fase_id' => $fase36->id, 'cargo_id' => $cargos['pecuario']->id],
                [
                    'cargo_receptor_id' => $cargos['pecuario']->id,
                    'tipo' => 'individual',
                    'estado' => 'pendiente',
                    'visita_confirmada' => false,
                ]
            );

            EmpalmeParticipante::firstOrCreate(['empalme_id' => $empalme3->id, 'asignacion_id' => $asigSaliente3->id], ['papel' => 'saliente']);
            EmpalmeParticipante::firstOrCreate(['empalme_id' => $empalme3->id, 'asignacion_id' => $asigReceptor3->id], ['papel' => 'receptor']);

            $items3 = [
                ['item' => 'Libro de registro de producción de leche y pesaje bovino', 'cantidad' => 1, 'estado' => 'Bueno', 'verificado' => false],
                ['item' => 'Botiquín veterinario institucional con instrumental quirúrgico básico', 'cantidad' => 1, 'estado' => 'Bueno', 'verificado' => false],
                ['item' => 'Llaves de la bodega de concentrados pecuarios', 'cantidad' => 2, 'estado' => 'Bueno', 'verificado' => false],
            ];
            foreach ($items3 as $it) {
                InventarioEmpalme::firstOrCreate(['empalme_id' => $empalme3->id, 'item' => $it['item']], $it);
            }
        }

        // 7. Crear Empalme 4: Líder Cacao (En curso)
        if ($cargos['cacao'] && isset($salientesMap['Líder de U. F. Productiva Cacao']) && $sergio) {
            $asigSaliente4 = Asignacion::firstOrCreate(
                ['person_id' => $salientesMap['Líder de U. F. Productiva Cacao']->id, 'cargo_id' => $cargos['cacao']->id, 'fase_id' => $fase35->id],
                ['estado' => 'finalizada', 'origen' => 'acta', 'fecha_inicio' => '2026-02-01', 'fecha_fin' => '2026-07-31']
            );
            $asigReceptor4 = Asignacion::firstOrCreate(
                ['person_id' => $sergio->id, 'cargo_id' => $cargos['cacao']->id, 'fase_id' => $fase36->id],
                ['estado' => 'asignada', 'origen' => 'acta', 'fecha_inicio' => '2026-08-01']
            );

            $empalme4 = Empalme::firstOrCreate(
                ['fase_id' => $fase36->id, 'cargo_id' => $cargos['cacao']->id],
                [
                    'cargo_receptor_id' => $cargos['cacao']->id,
                    'tipo' => 'individual',
                    'estado' => 'en_curso',
                    'visita_confirmada' => true,
                ]
            );

            EmpalmeParticipante::firstOrCreate(['empalme_id' => $empalme4->id, 'asignacion_id' => $asigSaliente4->id], ['papel' => 'saliente']);
            EmpalmeParticipante::firstOrCreate(['empalme_id' => $empalme4->id, 'asignacion_id' => $asigReceptor4->id], ['papel' => 'receptor']);

            $items4 = [
                ['item' => 'Tijeras de poda de altura y deschuponadores Felco', 'cantidad' => 4, 'estado' => 'Bueno', 'verificado' => true],
                ['item' => 'Cajones de fermentación de cacao y termómetros de alcohol', 'cantidad' => 2, 'estado' => 'Excelente', 'verificado' => true],
                ['item' => 'Llaves de marquesina solar de secado', 'cantidad' => 1, 'estado' => 'Bueno', 'verificado' => false],
            ];
            foreach ($items4 as $it) {
                InventarioEmpalme::firstOrCreate(['empalme_id' => $empalme4->id, 'item' => $it['item']], $it);
            }
        }
    }
}
