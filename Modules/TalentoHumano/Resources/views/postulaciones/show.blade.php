<x-talentohumano::layouts.master title="{{ $postulacion->nombre_completo }}">
    {{-- Regresar y Título --}}
    <div>
        <a href="{{ route('talentohumano.postulaciones.index') }}" class="text-sm font-semibold text-emerald-700 hover:underline flex items-center gap-1.5">
            <i class="fas fa-arrow-left"></i> Volver a Postulantes
        </a>

        <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-center gap-4">
                <span class="grid h-14 w-14 place-items-center rounded-2xl bg-[#064C3D] text-lg font-bold text-[#34D399] shadow-md shadow-[#064C3D]/20">
                    {{ $postulacion->iniciales }}
                </span>
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">
                        {{ $postulacion->nombre_completo }}
                    </h1>
                    <p class="text-sm text-slate-600">
                        Ficha {{ $postulacion->course->code ?? 'N/A' }} · {{ $postulacion->course->program->name ?? 'Programa' }} · {{ $postulacion->person->document_type ?? 'CC' }} {{ $postulacion->person->document_number ?? '' }}
                    </p>
                </div>
            </div>

            @if ($postulacion->puntaje_total)
                <div class="rounded-2xl bg-white px-5 py-3 text-right shadow-sm ring-1 ring-slate-200">
                    <p class="text-xs text-slate-400">Puntaje total</p>
                    <p class="text-3xl font-extrabold text-[#064C3D]">
                        {{ (int)$postulacion->puntaje_total }} <span class="text-sm font-semibold text-slate-400">/ 200</span>
                    </p>
                </div>
            @endif
        </div>

        {{-- Aviso de visibilidad para Instructor de Talento Humano --}}
        <p class="mt-4 flex items-start gap-2 rounded-2xl bg-sky-50 px-4 py-2.5 text-xs text-sky-900 ring-1 ring-sky-200/60">
            <i class="fas fa-shield-halved mt-0.5 text-sky-600"></i>
            <span>Ves los resultados porque tienes la designación <strong>Instructor de Talento Humano</strong>. El Gestor de Talento Humano no los ve.</span>
        </p>
    </div>

    {{-- Cuadrícula modular por bloques (2 columnas) --}}
    <div class="mt-4 grid gap-4 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="space-y-4">
            {{-- Bloque 1: Prueba psicotécnica --}}
            @include('talentohumano::postulaciones.partials.prueba_psicotecnica')

            {{-- Bloque 2: Evaluaciones de Entrevista --}}
            @include('talentohumano::postulaciones.partials.entrevistas')

            {{-- Bloque 3: Análisis y Concepto de Hoja de Vida --}}
            @include('talentohumano::postulaciones.partials.analisis_hv')
        </div>

        {{-- Bloque 4: Lateral con cargos postulados y documentos --}}
        @include('talentohumano::postulaciones.partials.sidebar_cargos_documentos')
    </div>
</x-talentohumano::layouts.master>
