@use('Modules\TalentoHumano\Entities\Cargo')
@php
    $esAdministrador = \Modules\TalentoHumano\Support\AccesoTH::esAdministrador(auth()->user());
    $tarjeta = 'rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70';
    $manual = [
        'rol_principal' => 'Rol principal',
        'actividades' => 'Actividades',
        'responsabilidades_sig' => 'Responsabilidades en el Sistema Integrado de Gestión',
        'documentacion' => 'Documentación',
    ];
@endphp

<x-talentohumano::layouts.lider :title="$cargo->nombre" activo="catalogo">
    <a href="{{ route('talentohumano.lider.catalogo.index') }}" class="text-sm font-semibold text-emerald-700 hover:underline">
        <i class="fas fa-arrow-left mr-1" aria-hidden="true"></i> Catálogo de cargos
    </a>

    <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full bg-[#064C3D] px-3 py-0.5 text-xs font-semibold text-white">{{ Cargo::NIVELES[$cargo->nivel] }}</span>
                <span @class(['rounded-full px-3 py-0.5 text-xs font-semibold', 'bg-emerald-100 text-emerald-800' => $cargo->estado === 'activo', 'bg-slate-200 text-slate-600' => $cargo->estado !== 'activo'])>
                    {{ $cargo->estado === 'activo' ? 'Activo' : 'Inactivo' }}
                </span>
            </div>
            <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">{{ $cargo->nombre }}</h1>
            <p class="mt-1 text-sm text-slate-600">{{ $cargo->area?->nombre }} @if ($cargo->subproceso) · {{ $cargo->subproceso }} @endif</p>
        </div>

        @if ($esAdministrador)
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('talentohumano.lider.catalogo.edit', $cargo) }}" class="inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-4 py-2 text-sm font-semibold text-white hover:bg-[#043A2F]">
                    <i class="fas fa-pen" aria-hidden="true"></i> Editar
                </a>
                @if ($cargo->estado === 'activo')
                    <form method="POST" action="{{ route('talentohumano.lider.catalogo.inactivar', $cargo) }}" data-titulo="¿Inactivar el cargo?"
                          data-confirmar="El cargo dejará de ofrecerse en la activación de cargos, pero conservará todo su historial.">
                        @csrf @method('PATCH')
                        <button class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold text-amber-700 ring-1 ring-amber-300 hover:bg-amber-50">
                            <i class="fas fa-ban" aria-hidden="true"></i> Inactivar
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('talentohumano.lider.catalogo.reactivar', $cargo) }}" data-titulo="¿Reactivar el cargo?"
                          data-confirmar="El cargo volverá a ofrecerse en la activación de cargos.">
                        @csrf @method('PATCH')
                        <button class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold text-emerald-700 ring-1 ring-emerald-300 hover:bg-emerald-50">
                            <i class="fas fa-rotate-left" aria-hidden="true"></i> Reactivar
                        </button>
                    </form>
                @endif
                <form method="POST" action="{{ route('talentohumano.lider.catalogo.destroy', $cargo) }}" data-titulo="¿Eliminar el cargo?" data-peligro
                      data-confirmar="Solo se pueden eliminar los cargos que nunca se han activado ni tienen asignaciones. Esta acción no se puede deshacer.">
                    @csrf @method('DELETE')
                    <button class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold text-red-600 ring-1 ring-red-200 hover:bg-red-50">
                        <i class="fas fa-trash" aria-hidden="true"></i> Eliminar
                    </button>
                </form>
            </div>
        @endif
    </div>

    @if (session('ofrecer_inactivar') && $esAdministrador && $cargo->estado === 'activo')
        <p class="mt-3 text-sm text-slate-600">Puedes usar el botón <strong>Inactivar</strong> en lugar de eliminarlo.</p>
    @endif

    <div class="mt-6 grid gap-4 xl:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="space-y-4">
            {{-- Datos del manual --}}
            @foreach ($manual as $campo => $titulo)
                @if ($cargo->$campo)
                    <section class="{{ $tarjeta }}">
                        <h2 class="font-bold text-[#064C3D]">{{ $titulo }}</h2>
                        <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ $cargo->$campo }}</p>
                    </section>
                @endif
            @endforeach

            <section class="{{ $tarjeta }}">
                <h2 class="font-bold text-[#064C3D]">Requisitos</h2>
                @if ($cargo->requisitos)
                    <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ $cargo->requisitos }}</p>
                @endif
                @if ($cargo->requisitosAdicionales->isNotEmpty())
                    <h3 class="mt-4 text-sm font-semibold text-slate-700">Requisitos que el sistema no puede verificar</h3>
                    <ul class="mt-2 space-y-2">
                        @foreach ($cargo->requisitosAdicionales as $requisito)
                            <li class="flex items-start gap-2 text-sm text-slate-600">
                                <i class="fas fa-circle-check mt-0.5 text-emerald-500" aria-hidden="true"></i>
                                <span>{{ $requisito->descripcion }}
                                    @if ($requisito->exige_soporte)
                                        <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-800">Exige soporte</span>
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
                @if (!$cargo->requisitos && $cargo->requisitosAdicionales->isEmpty())
                    <p class="mt-2 text-sm text-slate-400">Sin requisitos registrados.</p>
                @endif
            </section>
        </div>

        <aside class="space-y-4">
            <section class="{{ $tarjeta }}">
                <h2 class="font-bold text-[#064C3D]">Datos del cargo</h2>
                <dl class="mt-3 space-y-3 text-sm">
                    <div><dt class="text-xs text-slate-400">Aprendices requeridos</dt><dd class="text-xl font-extrabold text-[#064C3D]">{{ $cargo->numero_aprendices }}</dd></div>
                    <div><dt class="text-xs text-slate-400">Jefe inmediato</dt>
                        <dd class="font-semibold text-slate-700">
                            @if ($cargo->superior)
                                <a href="{{ route('talentohumano.lider.catalogo.show', $cargo->superior) }}" class="hover:text-[#064C3D] hover:underline">{{ $cargo->superior->nombre }}</a>
                            @else
                                {{ $cargo->jefe_inmediato_texto ?? 'Sin definir' }}
                                @if ($cargo->jefe_inmediato_texto) <span class="block text-xs font-normal text-slate-400">Alta dirección</span> @endif
                            @endif
                        </dd>
                    </div>
                    @foreach (['macroproceso' => 'Macroproceso', 'proceso' => 'Proceso', 'subproceso' => 'Subproceso'] as $campo => $titulo)
                        @if ($cargo->$campo)
                            <div><dt class="text-xs text-slate-400">{{ $titulo }}</dt><dd class="text-slate-700">{{ $cargo->$campo }}</dd></div>
                        @endif
                    @endforeach
                </dl>
            </section>

            <section class="{{ $tarjeta }}">
                <h2 class="font-bold text-[#064C3D]">Tecnólogos compatibles</h2>
                @forelse ($cargo->programas as $programa)
                    <div class="mt-3 rounded-2xl bg-[#E8F3EF]/60 px-3 py-2">
                        <p class="text-sm font-semibold text-slate-700">{{ $programa->name }}</p>
                        <p class="text-xs text-slate-500">SOFIA {{ $programa->sofia_code }} · {{ Cargo::TIPOS_PROGRAMA[$programa->pivot->tipo] }}</p>
                    </div>
                @empty
                    <p class="mt-3 rounded-2xl bg-amber-50 px-3 py-2 text-sm text-amber-800">
                        <i class="fas fa-triangle-exclamation mr-1" aria-hidden="true"></i>
                        Sin programas compatibles: no se propondrá automáticamente en la activación.
                    </p>
                @endforelse
            </section>

            @if ($cargo->subordinados->isNotEmpty())
                <section class="{{ $tarjeta }}">
                    <h2 class="font-bold text-[#064C3D]">Cargos a su cargo <span class="text-sm font-normal text-slate-400">({{ $cargo->subordinados->count() }})</span></h2>
                    <ul class="mt-3 space-y-1.5 text-sm">
                        @foreach ($cargo->subordinados as $subordinado)
                            <li><a href="{{ route('talentohumano.lider.catalogo.show', $subordinado) }}" class="text-slate-600 hover:text-[#064C3D] hover:underline">{{ $subordinado->nombre }}</a></li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </aside>
    </div>
</x-talentohumano::layouts.lider>
