@php
    $situaciones = [
        'presentada' => ['La presentó', 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
        'pendiente' => ['Pendiente', 'bg-amber-50 text-amber-700 ring-amber-200'],
        'no_evaluado' => ['No evaluado', 'bg-red-50 text-red-700 ring-red-200'],
        'sin_analisis' => ['Sin análisis de hoja de vida', 'bg-slate-100 text-slate-600 ring-slate-200'],
        'sin_habilitar' => ['Sin habilitar', 'bg-slate-100 text-slate-600 ring-slate-200'],
    ];
    $conteo = $postulantes->countBy('situacion');
    $claseCampo = 'mt-1 w-full rounded-xl border-0 px-3 py-2 text-sm ring-1 focus:ring-2 focus:ring-[#064C3D]';
@endphp
<x-talentohumano::layouts.master title="Prueba psicotécnica · Fase {{ $fase->numero }}">
    <x-talentohumano::encabezado titulo="Prueba psicotécnica · Fase {{ $fase->numero }}" :volver="route('talentohumano.fases.show', $fase->id)" hu="HU-025A · HU-025B · RN-012 · RN-013"
        subtitulo="Habilite la prueba a los postulantes que ya tienen el análisis de su hoja de vida y siga quién la presentó. Usted no ve las respuestas ni los puntajes." />

    @if ($bloqueo)
        <div class="rounded-2xl bg-white p-10 text-center text-slate-600 shadow-sm ring-1 ring-slate-200">
            <i class="fas fa-lock mr-1 text-amber-600" aria-hidden="true"></i> {{ $bloqueo }}
        </div>
    @else
        <x-talentohumano::errores />

        @if (session('sinAnalisis'))
            <div class="mb-6 rounded-2xl bg-amber-50 p-4 text-sm text-amber-900 ring-1 ring-amber-200">
                <p class="font-semibold"><i class="fas fa-triangle-exclamation mr-1" aria-hidden="true"></i> No se habilitó la prueba a estos postulantes:</p>
                <p class="mt-1">El análisis de la hoja de vida es prerrequisito de la prueba psicotécnica.</p>
                <ul class="mt-2 list-disc pl-5">
                    @foreach (session('sinAnalisis') as $nombre)
                        <li>{{ $nombre }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[20rem_1fr]">
            {{-- Habilitar (HU-025A) --}}
            <div class="h-fit rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="font-bold text-[#064C3D]">Habilitar la prueba</h2>
                <p class="mt-1 text-xs text-slate-500">
                    Fechas de la prueba en el cronograma:
                    {{ $inicio?->format('d/m/Y') ?? '—' }} al {{ $fin?->format('d/m/Y') ?? '—' }}.
                </p>

                @if ($pruebas->isEmpty())
                    <p class="mt-4 rounded-xl bg-amber-50 p-3 text-sm text-amber-900">
                        No hay una prueba psicotécnica disponible. Solicítela al Evaluador psicológico.
                    </p>
                @elseif ($soloConsulta)
                    <p class="mt-4 rounded-xl bg-sky-50 p-3 text-sm text-sky-900">Consulta del avance de la prueba, sin modificarlo.</p>
                @else
                    <form method="POST" action="{{ route('talentohumano.fases.prueba.habilitar', $fase->id) }}" class="mt-4 space-y-3">
                        @csrf
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Prueba</span>
                            <select name="cuestionario_id" class="{{ $claseCampo }} {{ $errors->has('cuestionario_id') ? 'ring-red-400' : 'ring-slate-300' }}">
                                @foreach ($pruebas as $prueba)
                                    <option value="{{ $prueba->id }}" @selected(old('cuestionario_id') == $prueba->id)>{{ $prueba->titulo }} (v{{ $prueba->version }})</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Plazo para presentarla</span>
                            <input type="date" name="plazo" value="{{ old('plazo', $fin?->format('Y-m-d')) }}"
                                   min="{{ max(today(), $inicio ?? today())->format('Y-m-d') }}" max="{{ $fin?->format('Y-m-d') }}"
                                   class="{{ $claseCampo }} {{ $errors->has('plazo') ? 'ring-red-400' : 'ring-slate-300' }}">
                        </label>
                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-[#064C3D] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#043A2F]">
                            <i class="fas fa-unlock" aria-hidden="true"></i> Habilitar prueba
                        </button>
                        <p class="text-xs text-slate-500">Se habilita a los postulantes con análisis de hoja de vida que aún no la tienen, y se les envía un correo con el plazo.</p>
                    </form>
                @endif
            </div>

            {{-- Seguimiento (HU-025B) --}}
            <div>
                <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach (['presentada' => 'La presentaron', 'pendiente' => 'Pendientes', 'no_evaluado' => 'No evaluados', 'sin_habilitar' => 'Sin habilitar'] as $clave => $texto)
                        <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                            <p class="text-2xl font-extrabold text-[#064C3D]">{{ $clave === 'sin_habilitar' ? ($conteo['sin_habilitar'] ?? 0) + ($conteo['sin_analisis'] ?? 0) : ($conteo[$clave] ?? 0) }}</p>
                            <p class="text-xs text-slate-500">{{ $texto }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr><th class="px-4 py-3">Postulante</th><th class="px-4 py-3">Ficha</th><th class="px-4 py-3">Plazo</th><th class="px-4 py-3">Prueba</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($postulantes as $postulante)
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-slate-900">{{ $postulante->nombre }}</td>
                                    <td class="px-4 py-3">{{ $postulante->ficha }}</td>
                                    <td class="px-4 py-3">{{ $postulante->limite_prueba ? \Illuminate\Support\Carbon::parse($postulante->limite_prueba)->format('d/m/Y') : '—' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $situaciones[$postulante->situacion][1] }}">{{ $situaciones[$postulante->situacion][0] }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-10 text-center text-slate-500">La convocatoria no tiene postulantes.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</x-talentohumano::layouts.master>
