@php
    $postulacion = $p->postulacion;
    $estadoPrueba = [
        'presentada' => ['Presentada', 'bg-emerald-100 text-emerald-800', 'fa-circle-check'],
        'no_evaluado' => ['No presentada en el plazo', 'bg-red-100 text-red-700', 'fa-circle-xmark'],
        'en_curso' => ['En curso, sin enviar', 'bg-amber-100 text-amber-800', 'fa-hourglass-half'],
        'habilitada' => ['Habilitada', 'bg-sky-100 text-sky-800', 'fa-clipboard-check'],
    ];
    $tiposEntrevista = ['tecnica' => ['Técnica', 'fa-screwdriver-wrench'], 'psicologica' => ['Psicológica', 'fa-brain']];
    $documentos = \Modules\TalentoHumano\Http\Controllers\AprendizController::DOCUMENTOS;
@endphp

<x-talentohumano::layouts.aprendiz title="Mi postulación" activo="postulacion" :p="$p">
    <div class="mb-6">
        <h1 class="text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">Mi postulación</h1>
        <p class="mt-1 text-sm text-slate-600">Solo tú ves esta información.</p>
    </div>

    @if (!$postulacion)
        @include('talentohumano::aprendiz._aviso', [
            'icono' => 'fa-file-circle-question',
            'titulo' => 'No tiene una postulación registrada',
            'texto' => $p->fichaHabilitada && $p->convocatoria ? null : 'Cuando tu ficha participe en una convocatoria podrás postularte desde aquí.',
            'enlace' => $p->fichaHabilitada && $estadoPostulacion === 'abierta' ? [route('talentohumano.aprendiz.convocatoria'), 'Ver la convocatoria'] : null,
        ])
    @else
        {{-- Resultado: solo con el acta de seleccionados publicada (HU-049 esc. 2, HU-050) --}}
        @if ($resultado)
            @php
                [$titulo, $texto, $color, $icono] = match ($resultado->tipo) {
                    'seleccionado' => ['Fuiste seleccionado como ' . $resultado->cargo, $resultado->preferencia ? "Cargo de tu opción {$resultado->preferencia}." : '', 'from-[#064C3D] to-[#043A2F] text-white', 'fa-award'],
                    'reubicado' => ['Fuiste reubicado como ' . $resultado->cargo, 'El comité de reubicación te asignó un cargo diferente a tus opciones.', 'from-sky-700 to-sky-900 text-white', 'fa-arrows-rotate'],
                    'sin_cargo' => ['Sin cargo asignado', 'El comité de reubicación no te asignó ningún cargo en esta fase.', 'from-slate-600 to-slate-800 text-white', 'fa-circle-minus'],
                    default => ['No fuiste seleccionado', 'No quedaste en ninguna de tus 3 opciones.', 'from-slate-600 to-slate-800 text-white', 'fa-circle-minus'],
                };
            @endphp
            <section class="mb-5 flex flex-wrap items-center gap-4 rounded-3xl bg-linear-to-r p-6 shadow-lg {{ $color }}">
                <span class="grid h-14 w-14 place-items-center rounded-2xl bg-white/10 text-2xl ring-1 ring-white/20"><i class="fas {{ $icono }}" aria-hidden="true"></i></span>
                <div class="min-w-0 flex-1">
                    @if ($resultado->tipo === 'reubicado')
                        <span class="rounded-full bg-white/20 px-2.5 py-0.5 text-xs font-bold uppercase">Reubicación</span>
                    @endif
                    <h2 class="mt-1 text-xl font-extrabold">{{ $titulo }}</h2>
                    @if ($texto)<p class="text-sm opacity-90">{{ $texto }}</p>@endif
                </div>
                <a href="{{ route('talentohumano.aprendiz.acta') }}" class="w-full rounded-full bg-white/15 px-4 py-2 text-center sm:w-auto text-sm font-semibold ring-1 ring-white/30 hover:bg-white/25">Ver el acta</a>
            </section>
        @else
            <p class="mb-5 flex items-center gap-2 rounded-2xl bg-sky-50 px-4 py-3 text-sm text-sky-900" role="status"><i class="fas fa-circle-info" aria-hidden="true"></i> Conocerás tu resultado cuando se publique el acta de seleccionados.</p>
        @endif

        <div class="grid gap-5 lg:grid-cols-2">
            <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="font-bold text-[#064C3D]">Mis opciones</h2>
                    <span class="rounded-full bg-[#E8F3EF] px-2.5 py-0.5 text-xs font-bold text-[#064C3D]">{{ ['postulado' => 'Postulado', 'no_evaluado' => 'No evaluado', 'seleccionado' => 'Seleccionado', 'reubicado' => 'Reubicado', 'no_seleccionado' => 'No seleccionado'][$resultado ? $postulacion->estado : ($postulacion->estado === 'no_evaluado' ? 'no_evaluado' : 'postulado')] ?? $postulacion->estado }}</span>
                </div>
                <p class="text-xs text-slate-500">Registrada el {{ $postulacion->created_at->format('d/m/Y \a \l\a\s h:i a') }}</p>
                <ol class="mt-4 space-y-2">
                    @foreach ($postulacion->opciones->sortBy('preferencia') as $opcion)
                        @php $cargo = $opcion->convocatoriaCargo?->cargoActivado?->cargo; @endphp
                        <li class="flex items-center gap-3 rounded-2xl bg-slate-50 p-3">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-[#064C3D] text-sm font-bold text-[#34D399]">{{ $opcion->preferencia }}</span>
                            <span class="min-w-0 flex-1"><span class="block font-semibold text-slate-800">{{ $cargo?->nombre }}</span><span class="text-xs text-slate-500">{{ $cargo?->area?->nombre }}</span></span>
                            <a href="{{ route('talentohumano.aprendiz.documento', [$opcion->id, 'tipo' => 'propuesta']) }}" class="text-xs font-semibold text-emerald-700 hover:underline">Propuesta</a>
                        </li>
                    @endforeach
                </ol>
                <h3 class="mt-5 text-xs font-bold uppercase tracking-wider text-slate-400">Documentos cargados</h3>
                <ul class="mt-2 space-y-1 text-sm">
                    @foreach ($postulacion->documentos as $doc)
                        <li><a href="{{ route('talentohumano.aprendiz.documento', $doc->id) }}" class="text-emerald-700 hover:underline"><i class="fas fa-file-arrow-down mr-1.5" aria-hidden="true"></i>{{ $documentos[$doc->tipo] ?? $doc->tipo }}</a> <span class="text-xs text-slate-400">· {{ $doc->nombre_original }}</span></li>
                    @endforeach
                </ul>
                <p class="mt-4 text-xs text-slate-500"><i class="fas fa-location-dot mr-1" aria-hidden="true"></i>{{ $postulacion->municipality?->name }}{{ $postulacion->vereda_ruta ? ' · ' . $postulacion->vereda_ruta : '' }} · <i class="fas fa-phone ml-1 mr-1" aria-hidden="true"></i>{{ $postulacion->telefono ?? '—' }}</p>
            </section>

            <div class="space-y-5">
                <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <h2 class="font-bold text-[#064C3D]">Prueba psicotécnica</h2>
                    @php [$texto, $color, $icono] = $estadoPrueba[$aplicacion?->estado ?? ''] ?? ['Sin presentar', 'bg-slate-100 text-slate-600', 'fa-clipboard']; @endphp
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                        <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-sm font-semibold {{ $color }}"><i class="fas {{ $icono }}" aria-hidden="true"></i> {{ $texto }}</span>
                        @if (!in_array($aplicacion?->estado, ['presentada', 'no_evaluado']) && $postulacion->estado !== 'no_evaluado')
                            <a href="{{ route('talentohumano.aprendiz.prueba') }}" class="text-sm font-semibold text-emerald-700 hover:underline">Ir a la prueba</a>
                        @endif
                    </div>
                    @if ($aplicacion?->fecha_presentacion)
                        <p class="mt-2 text-xs text-slate-500">Enviada el {{ \Illuminate\Support\Carbon::parse($aplicacion->fecha_presentacion)->format('d/m/Y h:i a') }}.</p>
                    @endif
                </section>

                <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <h2 class="font-bold text-[#064C3D]">Mis entrevistas</h2>
                    @forelse ($entrevistas as $entrevista)
                        @php [$tipo, $icono] = $tiposEntrevista[$entrevista->tipo] ?? [$entrevista->tipo, 'fa-comments']; $fecha = \Illuminate\Support\Carbon::parse($entrevista->fecha_hora); @endphp
                        <div class="mt-3 flex items-start gap-3 rounded-2xl bg-slate-50 p-3">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-[#E8F3EF] text-[#064C3D]"><i class="fas {{ $icono }}" aria-hidden="true"></i></span>
                            <div class="text-sm">
                                <p class="font-semibold text-slate-800">Entrevista {{ $tipo }} · {{ ucfirst($entrevista->modalidad) }}</p>
                                <p class="text-slate-600">{{ $fecha->translatedFormat('l j \d\e F \d\e Y') }}, {{ $fecha->format('h:i a') }}</p>
                                <p class="text-xs text-slate-500"><i class="fas fa-location-dot mr-1" aria-hidden="true"></i>{{ $entrevista->lugar ?: 'Lugar por confirmar' }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="mt-3 text-sm text-slate-500">Aún no tiene entrevistas programadas</p>
                    @endforelse
                </section>
            </div>
        </div>
    @endif
</x-talentohumano::layouts.aprendiz>
