@use('Modules\TalentoHumano\Http\Controllers\FaseController')
@use('Modules\TalentoHumano\Http\Controllers\CronogramaController')
@use('Modules\TalentoHumano\Http\Controllers\ConvocatoriaController')
@php
    // Pasos de la preparación en el orden obligatorio (RF-002). Cada uno se habilita cuando el anterior está completo.
    $pasos = [
        [
            'titulo' => 'Fichas de la fase',
            'icono' => 'fa-users-rectangle',
            'hu' => 'HU-017',
            'completo' => $resumen['fichas'] > 0,
            'detalle' => $resumen['fichas'] > 0
                ? "{$resumen['fichas']} fichas · {$resumen['aprendices']} aprendices"
                : 'Seleccione las fichas tecnólogo presenciales que ingresan a la fase.',
            'ruta' => route('talentohumano.fases.fichas.index', $fase->id),
            'bloqueo' => null,
        ],
        [
            'titulo' => 'Activación de cargos',
            'icono' => 'fa-sitemap',
            'hu' => 'HU-019',
            'completo' => $resumen['cargos'] > 0,
            'detalle' => $resumen['cargos'] > 0
                ? "{$resumen['cargos']} cargos · {$resumen['cupos']} aprendices requeridos"
                : 'Active los cargos que operarán en la fase y el número de aprendices de cada uno.',
            'ruta' => route('talentohumano.fases.cargos.edit', $fase->id),
            'bloqueo' => $resumen['fichas'] > 0 ? null : 'Primero debe seleccionar las fichas de la fase.',
        ],
        [
            'titulo' => 'Cronograma del proceso de selección',
            'icono' => 'fa-calendar-days',
            'hu' => 'HU-020',
            'completo' => $resumen['cronograma'] === 'aprobado',
            'detalle' => $resumen['cronograma']
                ? 'Estado: ' . CronogramaController::ESTADOS[$resumen['cronograma']]
                    . ($resumen['cronograma_modificacion'] ? ' · modificación: ' . mb_strtolower(CronogramaController::ESTADOS[$resumen['cronograma_modificacion']]) : '')
                : 'Defina las fechas de cada etapa y envíelo a aprobación del Instructor Líder.',
            'ruta' => route('talentohumano.fases.cronograma.edit', $fase->id),
            'bloqueo' => $resumen['cargos'] > 0 ? null : 'La activación de cargos es prerrequisito del cronograma.',
        ],
        [
            'titulo' => 'Convocatoria',
            'icono' => 'fa-bullhorn',
            'hu' => 'HU-022 · HU-023',
            'completo' => $resumen['convocatoria_publicada'],
            'detalle' => $resumen['convocatoria']
                ? 'Estado: ' . ConvocatoriaController::ESTADOS[$resumen['convocatoria']]
                    . ($resumen['convocatoria'] === 'borrador' && $resumen['cronograma'] !== 'aprobado' ? ' · se publica cuando el cronograma esté aprobado' : '')
                : 'Requisitos de cada cargo, umbral mínimo y política de selección.',
            'ruta' => route('talentohumano.fases.convocatoria.edit', $fase->id),
            'bloqueo' => in_array($resumen['cronograma'], ['enviado', 'aprobado'])
                ? null
                : 'Primero envíe el cronograma a aprobación del Instructor Líder.',
        ],
        // Proceso de selección: se habilita con la convocatoria publicada y cerrada (RN-004)
        [
            'titulo' => 'Prueba psicotécnica',
            'icono' => 'fa-brain',
            'hu' => 'HU-025A · HU-025B',
            'completo' => $resumen['pruebas_habilitadas'] > 0 && $resumen['pruebas_presentadas'] === $resumen['pruebas_habilitadas'],
            'detalle' => $resumen['pruebas_habilitadas'] > 0
                ? "{$resumen['pruebas_presentadas']} de {$resumen['pruebas_habilitadas']} postulantes la presentaron"
                : 'Habilite la prueba a los postulantes con análisis de hoja de vida y defina el plazo.',
            'ruta' => route('talentohumano.fases.prueba.index', $fase->id),
            'bloqueo' => match (true) {
                !$resumen['convocatoria_publicada'] => 'Primero publique la convocatoria.',
                !$resumen['convocatoria_cerrada'] => 'Se habilita cuando cierre la convocatoria.',
                default => null,
            },
        ],
        [
            'titulo' => 'Evaluadores de la convocatoria',
            'icono' => 'fa-user-tie',
            'hu' => 'HU-026A',
            'completo' => $resumen['evaluadores'] > 0,
            'detalle' => $resumen['evaluadores'] > 0
                ? "{$resumen['evaluadores']} evaluadores"
                : 'Instructores y psicólogos que apoyarán las entrevistas.',
            'ruta' => route('talentohumano.fases.evaluadores.index', $fase->id),
            'bloqueo' => $resumen['convocatoria_publicada'] ? null : 'Primero publique la convocatoria.',
        ],
        [
            'titulo' => 'Entrevistas',
            'icono' => 'fa-comments',
            'hu' => 'HU-027A · HU-027B · HU-027C',
            'completo' => false,
            'detalle' => $resumen['entrevistas'] > 0
                ? "{$resumen['entrevistas']} entrevistas programadas"
                : 'Programe las entrevistas técnica y psicológica y haga seguimiento a las calificaciones.',
            'ruta' => route('talentohumano.fases.entrevistas.index', $fase->id),
            'bloqueo' => $resumen['pruebas_presentadas'] > 0 ? null : 'Primero algún postulante debe presentar la prueba psicotécnica.',
        ],
        [
            'titulo' => 'Propuesta de selección',
            'icono' => 'fa-calculator',
            'hu' => 'HU-034',
            'completo' => $resumen['acta_publicada'],
            'detalle' => 'Calcule la propuesta cuando todas las entrevistas estén calificadas; se envía a validación del Instructor Líder.',
            'ruta' => route('talentohumano.fases.seleccion.show', $fase->id),
            'bloqueo' => $resumen['entrevistas'] > 0 ? null : 'Primero programe y califique las entrevistas.',
        ],
        [
            'titulo' => 'Acta de seleccionados',
            'icono' => 'fa-file-signature',
            'hu' => 'HU-028A · HU-028B',
            'completo' => $resumen['acta_publicada'],
            'detalle' => $resumen['acta_publicada']
                ? 'Publicada: las asignaciones de cargo quedaron creadas'
                : 'Revise el acta generada con las selecciones validadas y publíquela.',
            'ruta' => route('talentohumano.fases.acta.show', $fase->id),
            'bloqueo' => $resumen['entrevistas'] > 0 ? null : 'Primero programe y califique las entrevistas.',
        ],
        [
            'titulo' => 'Empalmes y vinculación',
            'icono' => 'fa-people-arrows',
            'hu' => 'HU-029A · HU-029B · HU-031 · HU-033',
            'completo' => $resumen['empalmes'] > 0 && $resumen['empalmes_cerrados'] === $resumen['empalmes'],
            'detalle' => $resumen['empalmes'] > 0
                ? "{$resumen['empalmes_cerrados']} de {$resumen['empalmes']} empalmes cerrados con visto bueno"
                : 'Prepare los empalmes: el sistema determina quién entrega y quién recibe cada cargo.',
            'ruta' => route('talentohumano.fases.empalmes.index', $fase->id),
            'bloqueo' => $resumen['acta_publicada'] ? null : 'Primero publique el acta de seleccionados.',
        ],
    ];
@endphp
<x-talentohumano::layouts.master title="Fase {{ $fase->numero }}">
    <x-talentohumano::encabezado titulo="Fase {{ $fase->numero }}" :volver="route('talentohumano.fases.index')"
        subtitulo="Inicio: {{ \Illuminate\Support\Carbon::parse($fase->fecha_inicio)->format('d/m/Y') }}{{ $fase->fecha_fin ? ' · Fin: ' . \Illuminate\Support\Carbon::parse($fase->fecha_fin)->format('d/m/Y') : '' }}">
        <x-talentohumano::estado :valor="$fase->estado" :etiqueta="FaseController::ESTADOS[$fase->estado] ?? null" class="text-sm" />
    </x-talentohumano::encabezado>

    @if ($resumen['convocatoria_publicada'])
        <div class="mb-6 rounded-2xl bg-amber-50 p-4 text-sm text-amber-800 ring-1 ring-amber-200">
            <i class="fas fa-lock mr-1" aria-hidden="true"></i>
            La convocatoria ya fue publicada: las fichas y los cargos activados de la fase ya no se pueden modificar.
        </div>
    @endif

    <ol class="space-y-4">
        @foreach ($pasos as $paso)
            <li @class([
                'flex flex-wrap items-center gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1',
                'ring-[#34D399]/60' => $paso['completo'],
                'ring-slate-200' => !$paso['completo'],
                'opacity-70' => $paso['bloqueo'],
            ])>
                <span @class([
                    'grid h-12 w-12 shrink-0 place-items-center rounded-2xl text-lg',
                    'bg-[#064C3D] text-[#34D399]' => $paso['completo'],
                    'bg-[#E8F3EF] text-[#064C3D]' => !$paso['completo'],
                ])>
                    <i class="fas {{ $paso['completo'] ? 'fa-check' : $paso['icono'] }}" aria-hidden="true"></i>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Paso {{ $loop->iteration }} · {{ $paso['hu'] }}</p>
                    <h2 class="font-bold text-slate-900">{{ $paso['titulo'] }}</h2>
                    <p class="text-sm text-slate-600">{{ $paso['detalle'] }}</p>
                    @if ($paso['bloqueo'])
                        <p class="mt-1 text-sm font-medium text-amber-700"><i class="fas fa-lock mr-1" aria-hidden="true"></i> {{ $paso['bloqueo'] }}</p>
                    @endif
                </div>
                @if ($paso['ruta'] && !$paso['bloqueo'])
                    <a href="{{ $paso['ruta'] }}" class="inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#043A2F]">
                        Abrir <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </a>
                @endif
            </li>
        @endforeach
    </ol>
</x-talentohumano::layouts.master>
