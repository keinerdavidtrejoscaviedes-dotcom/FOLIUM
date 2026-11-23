@php
    // Menú lateral fijo del Instructor: el mismo diseño del submódulo del Instructor Líder, con las secciones de su rol
    $usuario = auth()->user();
    $secciones = \Modules\TalentoHumano\Http\Controllers\TalentoHumanoController::seccionesDe($usuario);
    $etiquetas = \Modules\TalentoHumano\Http\Controllers\TalentoHumanoController::etiquetasDe($usuario);
    $enlace = fn (bool $actual) => $actual
        ? 'bg-[#064C3D] text-white shadow-md shadow-[#064C3D]/20'
        : 'text-slate-600 hover:bg-[#E8F3EF] hover:text-[#064C3D]';
    // Una sección está activa en su página principal y en sus páginas internas (mismo prefijo de ruta)
    $activa = fn (array $seccion) => isset($seccion['ruta'])
        && request()->routeIs(\Illuminate\Support\Str::beforeLast($seccion['ruta'], '.') . '.*');
    $enPanel = request()->routeIs('talentohumano.panel');
@endphp

<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:grid lg:grid-cols-[250px_minmax(0,1fr)] lg:gap-8 lg:py-8">
    {{-- Menú del submódulo --}}
    <aside class="mb-6 lg:mb-0">
        <div class="lg:sticky lg:top-6">
            <div class="rounded-3xl bg-[#064C3D] p-5 text-white">
                <p class="text-xs font-semibold uppercase tracking-[.18em] text-[#34D399]">Submódulo</p>
                <p class="mt-1 text-lg font-extrabold leading-tight">Instructor de TH</p>
                <div class="mt-3 flex flex-wrap gap-1.5">
                    @foreach ($etiquetas as $etiqueta)
                        <span class="rounded-full bg-white/10 px-2.5 py-0.5 text-[11px] font-semibold ring-1 ring-white/20">{{ $etiqueta }}</span>
                    @endforeach
                </div>
            </div>

            <nav aria-label="Secciones del submódulo" class="-mx-4 mt-3 flex gap-1.5 overflow-x-auto px-4 pb-1 lg:mx-0 lg:mt-4 lg:block lg:space-y-5 lg:overflow-visible lg:px-0">
                <a href="{{ route('talentohumano.panel') }}" @if ($enPanel) aria-current="page" @endif
                   class="flex shrink-0 items-center gap-3 rounded-2xl px-4 py-2.5 text-sm font-semibold transition {{ $enlace($enPanel) }}">
                    <i class="fas fa-gauge-high w-4 text-center" aria-hidden="true"></i> Mi panel
                </a>

                @if ($secciones->isNotEmpty())
                    <div class="contents lg:block">
                        <p class="hidden px-4 pb-1.5 text-[11px] font-bold uppercase tracking-[.18em] text-slate-400 lg:block">Mi trabajo</p>
                        <div class="contents lg:block lg:space-y-1">
                            @foreach ($secciones as $seccion)
                                @if (isset($seccion['ruta']) && Route::has($seccion['ruta']))
                                    <a href="{{ route($seccion['ruta']) }}" @if ($activa($seccion)) aria-current="page" @endif
                                       class="flex shrink-0 items-center gap-3 rounded-2xl px-4 py-2.5 text-sm font-semibold transition {{ $enlace($activa($seccion)) }}">
                                        <i class="fas {{ $seccion['icono'] }} w-4 text-center" aria-hidden="true"></i> {{ $seccion['titulo'] }}
                                    </a>
                                @else
                                    <span class="flex shrink-0 cursor-default items-center gap-3 rounded-2xl px-4 py-2.5 text-sm font-semibold text-slate-400">
                                        <i class="fas {{ $seccion['icono'] }} w-4 text-center" aria-hidden="true"></i> {{ $seccion['titulo'] }}
                                        <span class="ml-auto rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-500">Pronto</span>
                                    </span>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            </nav>
        </div>
    </aside>

    <section class="min-w-0">
        {{ $slot }}
    </section>
</div>
