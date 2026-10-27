@php
    $formatoPuntaje = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ',');
    $puntajeTotal = array_sum(array_column($preguntas, 'puntaje_maximo'));
@endphp
<x-talentohumano::layouts.master :title="$prueba->titulo">
    <x-talentohumano::encabezado :titulo="$prueba->titulo" :volver="route('talentohumano.pruebas.index')" hu="HU-039 · RF-007">
        @if ($esUltima)
            <a href="{{ route('talentohumano.pruebas.edit', $prueba->id) }}" class="inline-flex items-center gap-2 rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-[#064C3D] ring-1 ring-slate-300 hover:bg-slate-50">
                <i class="fas fa-pen" aria-hidden="true"></i> Editar
            </a>
            <form method="POST" action="{{ route('talentohumano.pruebas.estado', $prueba->id) }}">
                @csrf
                @method('PATCH')
                @if ($prueba->estado === 'vigente')
                    <input type="hidden" name="estado" value="inactivo">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50"
                            onclick="return confirm('¿Inactivar la prueba? No se podrá habilitar a nuevos postulantes.')">
                        <i class="fas fa-box-archive" aria-hidden="true"></i> Inactivar
                    </button>
                @else
                    <input type="hidden" name="estado" value="vigente">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#043A2F]">
                        <i class="fas fa-circle-check" aria-hidden="true"></i> Publicar
                    </button>
                @endif
            </form>
        @endif
    </x-talentohumano::encabezado>

    @unless ($esUltima)
        <div class="mb-6 rounded-2xl bg-amber-50 p-4 text-sm text-amber-800 ring-1 ring-amber-200">
            <i class="fas fa-clock-rotate-left mr-1" aria-hidden="true"></i>
            Está viendo una versión anterior de la prueba. Se conserva sin cambios porque tiene resultados de aprendices.
        </div>
    @endunless

    <div class="grid gap-6 lg:grid-cols-[1fr_18rem]">
        {{-- Vista previa: así verá el aprendiz la prueba --}}
        <div class="space-y-4">
            @foreach ($preguntas as $pregunta)
                <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-start justify-between gap-4">
                        <h2 class="font-semibold text-slate-900">{{ $loop->iteration }}. {{ $pregunta['enunciado'] }}</h2>
                        <span class="shrink-0 rounded-full bg-[#E8F3EF] px-2.5 py-0.5 text-xs font-semibold text-[#064C3D]">
                            {{ $formatoPuntaje($pregunta['puntaje_maximo']) }} pts
                        </span>
                    </div>

                    @if ($pregunta['imagen'])
                        <img src="{{ route('talentohumano.pruebas.imagen', $pregunta['id']) }}" alt="Imagen de la pregunta {{ $loop->iteration }}"
                             class="mt-3 max-h-64 rounded-xl object-contain ring-1 ring-slate-200">
                    @endif

                    <div class="mt-3 text-sm text-slate-600">
                        @switch($pregunta['tipo'])
                            @case('seleccion')
                                <ul class="space-y-1.5">
                                    @foreach ($pregunta['opciones'] as $opcion)
                                        <li class="flex items-center gap-2">
                                            <span class="h-4 w-4 rounded-full ring-1 ring-slate-300"></span>
                                            {{ $opcion['texto'] }}
                                            <span class="text-xs text-slate-400">({{ $formatoPuntaje($opcion['puntaje']) }} pts)</span>
                                        </li>
                                    @endforeach
                                </ul>
                                @break
                            @case('escala')
                                <div class="flex flex-wrap gap-2">
                                    @for ($valor = $pregunta['opciones']['min']; $valor <= $pregunta['opciones']['max']; $valor++)
                                        <span class="grid h-9 w-9 place-items-center rounded-full ring-1 ring-slate-300">{{ $valor }}</span>
                                    @endfor
                                </div>
                                @break
                            @default
                                <div class="h-16 rounded-xl bg-slate-50 ring-1 ring-slate-200"></div>
                                <p class="mt-1 text-xs text-slate-400">Respuesta abierta (no se califica automáticamente).</p>
                        @endswitch
                    </div>
                </article>
            @endforeach
        </div>

        {{-- Datos de la prueba y versiones --}}
        <aside class="space-y-4">
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-2"><dt class="text-slate-500">Estado</dt><dd><x-talentohumano::estado :valor="$prueba->estado" /></dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-slate-500">Versión</dt><dd class="font-semibold">v{{ $prueba->version }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-slate-500">Preguntas</dt><dd class="font-semibold">{{ count($preguntas) }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-slate-500">Puntaje máximo</dt><dd class="font-semibold">{{ $formatoPuntaje($puntajeTotal) }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-slate-500">Autor</dt><dd class="text-right">{{ $autor?->first_name }} {{ $autor?->first_last_name }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-slate-500">Respuestas</dt><dd>{{ $enUso ? 'Sí, ya se presentó' : 'Aún no' }}</dd></div>
                </dl>
            </div>

            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-sm font-bold text-[#064C3D]">Versiones</h2>
                <ol class="mt-3 space-y-2 text-sm">
                    @foreach ($versiones as $version)
                        <li class="flex items-center justify-between gap-2">
                            @if ($version->id == $prueba->id)
                                <span class="font-semibold">v{{ $version->version }} (esta)</span>
                            @else
                                <a href="{{ route('talentohumano.pruebas.show', $version->id) }}" class="font-semibold text-[#064C3D] hover:underline">v{{ $version->version }}</a>
                            @endif
                            <span class="text-xs text-slate-500">{{ \Illuminate\Support\Carbon::parse($version->created_at)->format('d/m/Y H:i') }}</span>
                        </li>
                    @endforeach
                </ol>
            </div>
        </aside>
    </div>
</x-talentohumano::layouts.master>
