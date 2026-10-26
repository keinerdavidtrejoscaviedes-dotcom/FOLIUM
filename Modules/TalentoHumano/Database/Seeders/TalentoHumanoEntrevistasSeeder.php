<?php

namespace Modules\TalentoHumano\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\TalentoHumano\Entities\Fase;
use Modules\TalentoHumano\Entities\Convocatoria;
use Modules\TalentoHumano\Entities\Postulacion;
use Modules\TalentoHumano\Entities\Cuestionario;
use Modules\TalentoHumano\Entities\CuestionarioPregunta;
use Modules\TalentoHumano\Entities\Entrevista;
use Modules\TalentoHumano\Entities\EntrevistaAspirante;
use Modules\TalentoHumano\Entities\EntrevistaEvaluador;
use Modules\TalentoHumano\Entities\Calificacion;
use App\Models\User;

class TalentoHumanoEntrevistasSeeder extends Seeder
{
    public function run(): void
    {
        $evaluador = User::where('nickname', 'th.instructor')->first();
        $evaluadorId = $evaluador->person_id ?? 1;

        $convocatoria = Convocatoria::first();
        if (!$convocatoria) {
            return;
        }

        // 1. Cuestionario / Formato de preguntas técnicas del evaluador (HU-040 · Mockup INS-08)
        $cuestionario = Cuestionario::firstOrCreate(
            ['autor_id' => $evaluadorId, 'tipo' => 'entrevista_tecnica', 'version' => 1],
            [
                'titulo' => 'Formato de Entrevista Técnica - General',
                'estado' => 'vigente',
            ]
        );

        $preguntasBase = [
            ['orden' => 1, 'enunciado' => 'Presentación personal', 'puntaje' => 10],
            ['orden' => 2, 'enunciado' => 'Tipo de comunicación (pasiva, agresiva o asertiva)', 'puntaje' => 15],
            ['orden' => 3, 'enunciado' => 'Actitud y proyección personal y laboral', 'puntaje' => 15],
            ['orden' => 4, 'enunciado' => 'Conocimientos técnicos del cargo', 'puntaje' => 30],
            ['orden' => 5, 'enunciado' => '¿Cómo programaría el riego de un cultivo de cacao en época seca?', 'puntaje' => 15],
            ['orden' => 6, 'enunciado' => '¿Qué registros de producción llevaría en su unidad y para qué?', 'puntaje' => 15],
        ];

        foreach ($preguntasBase as $pb) {
            CuestionarioPregunta::firstOrCreate(
                ['cuestionario_id' => $cuestionario->id, 'orden' => $pb['orden']],
                [
                    'enunciado' => $pb['enunciado'],
                    'tipo' => 'escala',
                    'puntaje_maximo' => $pb['puntaje'],
                ]
            );
        }

        // 2. Entrevistas asignadas al evaluador (HU-042 · Mockup INS-07)
        $postulaciones = Postulacion::with('person')->limit(5)->get();

        $datosEntrevistas = [
            ['lugar' => 'Laboratorio ambiental', 'modalidad' => 'individual', 'fecha_hora' => '2026-10-02 09:00:00', 'calificada' => false],
            ['lugar' => 'Sala de Talento Humano', 'modalidad' => 'individual', 'fecha_hora' => '2026-10-02 10:00:00', 'calificada' => false],
            ['lugar' => 'Sala de Talento Humano', 'modalidad' => 'individual', 'fecha_hora' => '2026-10-03 08:00:00', 'calificada' => false],
            ['lugar' => 'Sala de Talento Humano', 'modalidad' => 'individual', 'fecha_hora' => '2026-10-03 09:00:00', 'calificada' => false],
            ['lugar' => 'Sala de Talento Humano', 'modalidad' => 'individual', 'fecha_hora' => '2026-10-01 09:00:00', 'calificada' => true, 'puntaje' => 78.00],
        ];

        foreach ($postulaciones as $idx => $post) {
            $data = $datosEntrevistas[$idx] ?? $datosEntrevistas[0];

            $entrevista = Entrevista::create([
                'convocatoria_id' => $convocatoria->id,
                'tipo' => 'tecnica',
                'modalidad' => $data['modalidad'],
                'fecha_hora' => $data['fecha_hora'],
                'lugar' => $data['lugar'],
            ]);

            EntrevistaAspirante::create([
                'entrevista_id' => $entrevista->id,
                'postulacion_id' => $post->id,
            ]);

            EntrevistaEvaluador::create([
                'entrevista_id' => $entrevista->id,
                'person_id' => $evaluadorId,
            ]);

            // Si está calificada (HU-041)
            if ($data['calificada']) {
                Calificacion::create([
                    'entrevista_id' => $entrevista->id,
                    'postulacion_id' => $post->id,
                    'evaluador_id' => $evaluadorId,
                    'cuestionario_id' => $cuestionario->id,
                    'puntaje' => $data['puntaje'],
                    'concepto' => 'apto',
                    'observaciones' => 'Buen desenvolvimiento técnico y seguridad en las respuestas.',
                ]);
            }
        }
    }
}
