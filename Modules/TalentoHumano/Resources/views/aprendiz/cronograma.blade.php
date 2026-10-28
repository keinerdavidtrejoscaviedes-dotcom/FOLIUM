@php
    $estilo = [
        'hecho' => ['bg-[#064C3D] text-[#34D399]', 'fa-check', 'text-slate-500', 'Terminada'],
        'actual' => ['bg-amber-400 text-amber-950 ring-4 ring-amber-100', 'fa-location-dot', 'text-slate-900 font-bold', 'En curso'],
        'pendiente' => ['bg-slate-100 text-slate-400', 'fa-clock', 'text-slate-600', 'Próxima'],
    ];
@endphp

<x-talentohumano::layouts.aprendiz title="Cronograma" activo="cronograma" :p="$p">
    <div class="mb-6">
        <h1 class="text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">Cronograma del proceso de selección</h1>
        <p class="mt-1 text-sm text-slate-600">Fechas aprobadas por el Instructor Líder para cada etapa{{ $p->fase ? ' de la ' . $p->fase->nombre_completo : '' }}.</p>
    </div>

    @if (!$p->fichaHabilitada)
        @include('talentohumano::aprendiz._aviso', ['titulo' => 'Tu ficha no participa en la convocatoria actual', 'texto' => 'El cronograma solo está disponible para los aprendices de las fichas habilitadas en la convocatoria.'])
    @elseif (!$p->cronograma)
        @include('talentohumano::aprendiz._aviso', ['icono' => 'fa-calendar-xmark', 'titulo' => 'El cronograma del proceso de selección aún no ha sido publicado', 'texto' => 'Cuando el Instructor Líder lo apruebe, verás aquí las fechas y te llegará un correo con ellas.'])
    @else
        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70 sm:p-8">
            <ol class="relative space-y-6 border-l-2 border-slate-100 pl-8">
                @foreach ($etapas as $etapa)
                    @php [$punto, $icono, $texto, $etiqueta] = $estilo[$etapa->situacion]; @endphp
                    <li class="relative" @if ($etapa->situacion === 'actual') aria-current="step" @endif>
                        <span class="absolute -left-[45px] grid h-8 w-8 place-items-center rounded-full text-xs {{ $punto }}"><i class="fas {{ $icono }}" aria-hidden="true"></i></span>
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <p class="{{ $texto }}">{{ $etapa->nombre }}</p>
                            <span class="text-xs font-semibold {{ $etapa->situacion === 'actual' ? 'text-amber-700' : 'text-slate-400' }}">{{ $etiqueta }}</span>
                        </div>
                        <p class="mt-0.5 text-sm text-slate-500">
                            @if (!$etapa->inicio)
                                Sin fecha definida
                            @elseif ($etapa->rango)
                                Del {{ $etapa->inicio->format('d/m/Y') }} al {{ $etapa->fin->format('d/m/Y') }}
                            @else
                                {{ $etapa->inicio->format('d/m/Y') }}
                            @endif
                        </p>
                    </li>
                @endforeach
            </ol>
            <p class="mt-6 border-t border-slate-100 pt-4 text-xs text-slate-500"><i class="fas fa-circle-check mr-1 text-emerald-600" aria-hidden="true"></i>Aprobado el {{ $p->cronograma->fecha_aprobacion?->format('d/m/Y') }}.</p>
        </section>
    @endif
</x-talentohumano::layouts.aprendiz>
