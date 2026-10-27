@use('Modules\TalentoHumano\Http\Controllers\FaseController')
@use('Modules\TalentoHumano\Http\Controllers\CronogramaController')
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
                : 'Defina las fechas de cada etapa y envíelo a aprobación del Instructor Líder.',
            'ruta' => route('talentohumano.fases.cronograma.edit', $fase->id),
            'bloqueo' => $resumen['cargos'] > 0 ? null : 'La activación de cargos es prerrequisito del cronograma.',
        ],
        [
            'titulo' => 'Convocatoria',
            'icono' => 'fa-bullhorn',
            'hu' => 'HU-022',
            'completo' => $resumen['convocatoria_publicada'],
            'detalle' => 'Cargos ofrecidos, requisitos y fichas habilitadas.',
            'ruta' => null,
            'bloqueo' => $resumen['cronograma'] === 'aprobado'
                ? 'Próximamente.'
                : 'El cronograma aprobado es prerrequisito para publicar la convocatoria.',
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
