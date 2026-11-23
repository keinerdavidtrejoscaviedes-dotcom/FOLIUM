{{-- Bloque: seguimiento del postulante sin resultados (HU-032 esc. 1, HU-025B esc. 2). Lo ve el Gestor de Talento Humano:
     si presentó la prueba y sus entrevistas programadas, sin puntajes, respuestas, calificaciones ni observaciones. --}}
@php
    $estadosPrueba = [
        'presentada' => ['Presentada', 'bg-emerald-50 text-emerald-700', 'fa-circle-check'],
        'pendiente' => ['Pendiente de presentar', 'bg-amber-50 text-amber-700', 'fa-hourglass-half'],
        'no_evaluado' => ['No evaluado: no la presentó en el plazo', 'bg-red-50 text-red-700', 'fa-circle-xmark'],
        'sin_habilitar' => ['Aún no habilitada', 'bg-slate-100 text-slate-600', 'fa-lock'],
    ];
    [$textoPrueba, $colorPrueba, $iconoPrueba] = $estadosPrueba[$seguimiento['prueba']];
@endphp
<section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
    <h2 class="font-bold text-[#064C3D] flex items-center gap-2">
        <i class="fas fa-list-check text-emerald-600" aria-hidden="true"></i>
        <span>Seguimiento del proceso</span>
    </h2>

    <div class="mt-4 flex flex-wrap items-center justify-between gap-2 rounded-2xl bg-slate-50 px-4 py-3 ring-1 ring-slate-200">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Prueba psicotécnica</p>
            <p class="text-xs text-slate-500">
                @if ($seguimiento['presentada_el'])
                    Presentada el {{ $seguimiento['presentada_el'] }}.
                @elseif ($seguimiento['plazo'] && $seguimiento['prueba'] === 'pendiente')
                    Plazo hasta el {{ $seguimiento['plazo'] }}.
                @endif
            </p>
        </div>
        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $colorPrueba }}"><i class="fas {{ $iconoPrueba }} mr-1" aria-hidden="true"></i>{{ $textoPrueba }}</span>
    </div>

    <h3 class="mt-5 text-xs font-bold uppercase tracking-wide text-slate-500">Entrevistas programadas</h3>
    @forelse ($seguimiento['entrevistas'] as $entrevista)
        <div class="mt-2 flex flex-wrap items-center justify-between gap-2 rounded-2xl px-4 py-3 ring-1 ring-slate-200">
            <span class="text-sm font-semibold text-slate-800">{{ $entrevista->tipo }} · {{ $entrevista->modalidad }}</span>
            <span class="text-xs text-slate-600"><i class="fas fa-calendar-day mr-1" aria-hidden="true"></i>{{ $entrevista->cuando }} · {{ $entrevista->lugar }}</span>
        </div>
    @empty
        <p class="mt-2 text-sm text-slate-500">Aún no tiene entrevistas programadas.</p>
    @endforelse
</section>
