@use('Modules\TalentoHumano\Entities\Designacion')
@php
    $esAdministrador = \Modules\TalentoHumano\Support\AccesoTH::esAdministrador(auth()->user());
    $campo = 'w-full rounded-xl border bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#34D399]/40';
    $chip = ['lider_estrategia' => 'bg-[#064C3D] text-white', 'instructor_th' => 'bg-emerald-100 text-emerald-800', 'instructor_tecnico' => 'bg-slate-100 text-slate-600'];
    $nombre = fn ($persona) => trim($persona->first_name . ' ' . $persona->first_last_name . ' ' . $persona->second_last_name);
    $iniciales = fn (string $texto) => collect(explode(' ', $texto))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('');
    $opcionInstructor = fn ($i) => $i->nombre . ' · CC ' . number_format((float) $i->documento, 0, ',', '.');
    $sinLider = !$vigentes->contains('tipo', 'lider_estrategia');
    $pestanas = ['vigentes' => ['Vigentes', $vigentes->count()], 'historial' => ['Historial', $historial->count()], 'psicologos' => ['Psicólogos', $psicologos->count()]];
    $error = fn (string $bolsa, string $campo) => $errors->getBag($bolsa)->first($campo);
@endphp

<x-talentohumano::layouts.lider title="Designaciones" activo="designaciones">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">Designaciones</h1>
            <p class="mt-1 text-sm text-slate-600">Las funciones de los instructores en el proceso. Se asignan a la función, no a la persona.</p>
        </div>
        @if ($esAdministrador)
            <button type="button" data-abrir="#modal-designar" class="inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#064C3D]/20 transition hover:bg-[#043A2F]">
                <i class="fas fa-plus" aria-hidden="true"></i> Nueva designación
            </button>
        @endif
    </div>

    {{-- Condiciones mínimas (HU-011, RN-033) --}}
    @if ($sinLider)
        <div class="mt-4 flex flex-wrap items-center gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="alert">
            <i class="fas fa-triangle-exclamation text-amber-500" aria-hidden="true"></i>
            <span class="min-w-0 flex-1"><strong>No hay un Líder de la estrategia vigente.</strong> Sin esta designación no se puede enviar el cronograma a aprobación ni iniciar el proceso de selección.</span>
            @if ($esAdministrador)
                <button type="button" data-abrir="#modal-designar" data-marcar="lider_estrategia" class="rounded-full bg-amber-500 px-3 py-1 text-xs font-semibold text-white hover:bg-amber-600">Asignar designación</button>
            @endif
        </div>
    @endif
    @if ($psicologos->isEmpty())
        <div class="mt-2 flex items-center gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="alert">
            <i class="fas fa-triangle-exclamation text-amber-500" aria-hidden="true"></i>
            <span><strong>No hay ningún psicólogo activo.</strong> Se necesita al menos uno para iniciar el proceso de selección.</span>
        </div>
    @endif

    {{-- Resumen por función --}}
    <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['Líder de la estrategia', $conteo['lider_estrategia'] ?? 0, 'fa-user-tie', 'bg-[#064C3D] text-[#34D399]', $sinLider],
            ['Instructor de Talento Humano', $conteo['instructor_th'] ?? 0, 'fa-chalkboard-user', 'bg-[#E8F3EF] text-[#064C3D]', false],
            ['Instructores técnicos', $conteo['instructor_tecnico'] ?? 0, 'fa-screwdriver-wrench', 'bg-[#E8F3EF] text-[#064C3D]', false],
            ['Psicólogos activos', $psicologos->count(), 'fa-brain', 'bg-sky-50 text-sky-700', $psicologos->isEmpty()],
        ] as [$texto, $valor, $icono, $color, $alerta])
            <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70">
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-xl {{ $color }}"><i class="fas {{ $icono }}" aria-hidden="true"></i></span>
                    <div><p class="text-xs text-slate-500">{{ $texto }}</p><p @class(['text-xl font-extrabold', 'text-amber-600' => $alerta, 'text-[#064C3D]' => !$alerta])>{{ $valor }}</p></div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Pestañas --}}
    <div class="mt-6 flex flex-wrap gap-2" role="tablist" aria-label="Designaciones">
        @foreach ($pestanas as $clave => [$etiqueta, $cantidad])
            <a href="{{ route('talentohumano.lider.designaciones.index', ['vista' => $clave]) }}" role="tab" aria-selected="{{ $vista === $clave ? 'true' : 'false' }}"
               class="rounded-full px-4 py-1.5 text-sm font-semibold transition {{ $vista === $clave ? 'bg-[#064C3D] text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:ring-[#064C3D]' }}">
                {{ $etiqueta }} <span class="ml-1 text-xs opacity-70">{{ $cantidad }}</span>
            </a>
        @endforeach
    </div>

    @if ($vista === 'vigentes')
        <div class="mt-4 overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200/70">
            @if ($vigentes->isEmpty())
                <div class="px-6 py-14 text-center text-sm text-slate-500">
                    <i class="fas fa-user-tag mb-2 block text-3xl text-slate-300" aria-hidden="true"></i>
                    Todavía no hay designaciones vigentes.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Instructor</th>
                                <th class="px-4 py-3 font-semibold">Designación</th>
                                <th class="hidden px-4 py-3 font-semibold lg:table-cell">Vigencia</th>
                                <th class="hidden px-4 py-3 font-semibold xl:table-cell">Asignada</th>
                                @if ($esAdministrador) <th class="px-4 py-3"><span class="sr-only">Acciones</span></th> @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($vigentes as $designacion)
                                @php $persona = $nombre($designacion->persona); @endphp
                                <tr class="transition hover:bg-[#E8F3EF]/40">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-[#E8F3EF] text-xs font-bold text-[#064C3D]">{{ $iniciales($persona) }}</span>
                                            <div class="min-w-0"><p class="font-semibold text-slate-800">{{ $persona }}</p><p class="truncate text-xs text-slate-500">{{ $correos[$designacion->person_id] ?? '' }}</p></div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $chip[$designacion->tipo] }}">{{ Designacion::TIPOS[$designacion->tipo] }}</span>
                                        @if ($designacion->programa) <p class="mt-1 text-xs text-slate-500">{{ $designacion->programa->name }}</p> @endif
                                    </td>
                                    <td class="hidden px-4 py-3 text-slate-600 lg:table-cell">{{ $designacion->fase ? 'Solo la ' . $designacion->fase->nombre() : 'Indefinida' }}</td>
                                    <td class="hidden px-4 py-3 text-xs text-slate-500 xl:table-cell">
                                        {{ $designacion->created_at->format('d/m/Y') }} · {{ $designacion->asignadaPor ? $nombre($designacion->asignadaPor) : '' }}
                                    </td>
                                    @if ($esAdministrador)
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex justify-end gap-1">
                                                <button type="button" data-abrir="#modal-reasignar" data-id="{{ $designacion->id }}" data-nombre="{{ $persona }}" data-funcion="{{ $designacion->nombreTipo() }}"
                                                        class="grid h-8 w-8 place-items-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-[#064C3D]" title="Reasignar" aria-label="Reasignar la designación de {{ $persona }}">
                                                    <i class="fas fa-right-left" aria-hidden="true"></i></button>
                                                <button type="button" data-abrir="#modal-retirar" data-id="{{ $designacion->id }}" data-nombre="{{ $persona }}" data-funcion="{{ $designacion->nombreTipo() }}"
                                                        class="grid h-8 w-8 place-items-center rounded-lg text-slate-500 hover:bg-red-50 hover:text-red-600" title="Retirar" aria-label="Retirar la designación de {{ $persona }}">
                                                    <i class="fas fa-user-minus" aria-hidden="true"></i></button>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @elseif ($vista === 'historial')
        <div class="mt-4 overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200/70">
            @if ($historial->isEmpty())
                <div class="px-6 py-14 text-center text-sm text-slate-500">
                    <i class="fas fa-clock-rotate-left mb-2 block text-3xl text-slate-300" aria-hidden="true"></i>
                    Aún no se ha retirado ninguna designación.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                            <tr><th class="px-4 py-3 font-semibold">Instructor</th><th class="px-4 py-3 font-semibold">Designación</th><th class="px-4 py-3 font-semibold">Periodo</th><th class="hidden px-4 py-3 font-semibold lg:table-cell">Motivo del retiro</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-600">
                            @foreach ($historial as $designacion)
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-slate-800">{{ $nombre($designacion->persona) }}</td>
                                    <td class="px-4 py-3">{{ $designacion->nombreTipo() }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $designacion->created_at->format('d/m/Y') }} – {{ $designacion->fecha_retiro->format('d/m/Y') }}</td>
                                    <td class="hidden px-4 py-3 lg:table-cell">
                                        {{ $designacion->motivo_retiro }}
                                        @if ($designacion->retiradaPor) <span class="block text-xs text-slate-400">Retiró: {{ $nombre($designacion->retiradaPor) }}</span> @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @else
        <div class="mt-4 space-y-3">
            <div class="flex items-start gap-3 rounded-2xl bg-sky-50 px-4 py-3 text-sm text-sky-900">
                <i class="fas fa-circle-info mt-0.5" aria-hidden="true"></i>
                <span>Los psicólogos no reciben designación: su rol <strong>Psicólogo</strong> los habilita como evaluadores psicológicos. Esta lista es de solo consulta.</span>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                @forelse ($psicologos as $psicologo)
                    <div class="flex items-center gap-3 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70">
                        <span class="grid h-10 w-10 place-items-center rounded-full bg-sky-100 text-sm font-bold text-sky-800">{{ $iniciales($psicologo->nombre) }}</span>
                        <div class="min-w-0"><p class="font-semibold text-slate-800">{{ $psicologo->nombre }}</p><p class="truncate text-xs text-slate-500">{{ $psicologo->correo }} · Activo</p></div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No hay usuarios activos con rol Psicólogo.</p>
                @endforelse
            </div>
        </div>
    @endif

    @if ($esAdministrador)
        {{-- Nueva designación (HU-009) --}}
        <dialog id="modal-designar" aria-labelledby="titulo-designar" class="m-auto w-[min(94vw,36rem)] rounded-3xl p-0 shadow-2xl backdrop:bg-slate-900/50" @if ($errors->designar->any()) data-abrir-al-cargar @endif>
            <form method="POST" action="{{ route('talentohumano.lider.designaciones.store') }}" class="p-6" novalidate>
                @csrf
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 id="titulo-designar" class="text-lg font-bold text-slate-900">Nueva designación</h2>
                        <p class="text-sm text-slate-500">Un instructor puede tener varias designaciones a la vez.</p>
                    </div>
                    <button type="button" data-cerrar class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 hover:bg-slate-100" aria-label="Cerrar"><i class="fas fa-xmark" aria-hidden="true"></i></button>
                </div>

                <div class="mt-5 space-y-4">
                    <div>
                        <label for="instructor" class="mb-1 block text-sm font-semibold text-slate-700">Instructor <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <i class="fas fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400" aria-hidden="true"></i>
                            <input id="instructor" list="lista-instructores" data-persona="#designar-persona" autocomplete="off" placeholder="Busca por nombre o documento…"
                                   value="{{ ($i = $instructores->firstWhere('id', (int) old('person_id'))) && $errors->designar->any() ? $opcionInstructor($i) : '' }}"
                                   class="{{ $campo }} pl-8 {{ $error('designar', 'person_id') ? 'border-red-400' : 'border-slate-200 focus:border-[#064C3D]' }}">
                            <input type="hidden" name="person_id" id="designar-persona" value="{{ $errors->designar->any() ? old('person_id') : '' }}">
                        </div>
                        @if ($error('designar', 'person_id')) <p class="mt-1 text-xs font-semibold text-red-600">{{ $error('designar', 'person_id') }}</p>
                        @else <p class="mt-1 text-xs text-slate-500">{{ $instructores->count() }} instructores disponibles. Los psicólogos no aparecen en esta lista.</p> @endif
                    </div>

                    <fieldset>
                        <legend class="mb-2 text-sm font-semibold text-slate-700">Designación <span class="text-red-500">*</span></legend>
                        <div class="grid gap-2">
                            @foreach (Designacion::TIPOS as $tipo => $texto)
                                <label class="flex items-start gap-3 rounded-2xl p-3 ring-1 ring-slate-200 has-[:checked]:bg-[#E8F3EF]/50 has-[:checked]:ring-[#064C3D]">
                                    <input type="radio" name="tipo" value="{{ $tipo }}" @checked(old('tipo', 'instructor_tecnico') === $tipo) class="mt-1 accent-[#064C3D]">
                                    <span class="flex-1">
                                        <span class="block text-sm font-semibold text-slate-700">{{ $texto }}</span>
                                        <span class="text-xs text-slate-500">{{ Designacion::DESCRIPCIONES[$tipo] }}</span>
                                        @if ($tipo === 'instructor_tecnico')
                                            <select name="program_id" aria-label="Programa de formación" class="mt-2 {{ $campo }} {{ $error('designar', 'program_id') ? 'border-red-400' : 'border-slate-200 focus:border-[#064C3D]' }}">
                                                <option value="">Programa de formación…</option>
                                                @foreach ($programas as $programa)
                                                    <option value="{{ $programa->id }}" @selected((string) old('program_id') === (string) $programa->id)>{{ $programa->name }} · SOFIA {{ $programa->sofia_code }}</option>
                                                @endforeach
                                            </select>
                                            @if ($error('designar', 'program_id')) <span class="mt-1 block text-xs font-semibold text-red-600">{{ $error('designar', 'program_id') }}</span> @endif
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @if ($error('designar', 'tipo')) <p class="mt-2 text-xs font-semibold text-red-600">{{ $error('designar', 'tipo') }}</p> @endif
                    </fieldset>

                    <div>
                        <label for="vigencia" class="mb-1 block text-sm font-semibold text-slate-700">Vigencia <span class="text-red-500">*</span></label>
                        <select id="vigencia" name="vigencia" class="{{ $campo }} border-slate-200 focus:border-[#064C3D]">
                            <option value="indefinida">Indefinida</option>
                            @if ($fase) <option value="fase" @selected(old('vigencia') === 'fase')>Solo la {{ $fase->nombre() }}</option> @endif
                        </select>
                        @if (!$fase) <p class="mt-1 text-xs text-slate-500">No hay una fase en curso: por ahora solo se puede asignar con vigencia indefinida.</p> @endif
                        @if ($error('designar', 'vigencia')) <p class="mt-1 text-xs font-semibold text-red-600">{{ $error('designar', 'vigencia') }}</p> @endif
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" data-cerrar class="rounded-full px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancelar</button>
                    <button class="rounded-full bg-[#064C3D] px-5 py-2 text-sm font-semibold text-white hover:bg-[#043A2F]"><i class="fas fa-check mr-1" aria-hidden="true"></i> Asignar</button>
                </div>
            </form>
        </dialog>

        {{-- Reasignar (HU-010, esc. 2) --}}
        <dialog id="modal-reasignar" aria-labelledby="titulo-reasignar" class="m-auto w-[min(94vw,32rem)] rounded-3xl p-0 shadow-2xl backdrop:bg-slate-900/50"
                @if ($errors->reasignar->any()) data-abrir-al-cargar data-id="{{ old('designacion_id') }}" @endif>
            <form method="POST" data-accion="{{ route('talentohumano.lider.designaciones.reasignar', '__ID__') }}" class="p-6" novalidate>
                @csrf
                <input type="hidden" name="designacion_id" data-campo-id value="{{ old('designacion_id') }}">
                <h2 id="titulo-reasignar" class="text-lg font-bold text-slate-900">Reasignar designación</h2>
                <p class="text-sm text-slate-500">La función pasa a otro instructor sin cambiar el proceso. Quien la ejercía queda en el historial.</p>
                <div class="mt-4 rounded-2xl bg-[#E8F3EF]/60 px-4 py-3 text-sm">
                    <p class="font-semibold text-slate-800" data-campo-nombre></p>
                    <p class="text-xs text-slate-500" data-campo-funcion></p>
                </div>
                <div class="mt-4 space-y-4">
                    <div>
                        <label for="reasignar-instructor" class="mb-1 block text-sm font-semibold text-slate-700">Nuevo instructor <span class="text-red-500">*</span></label>
                        <input id="reasignar-instructor" list="lista-instructores" data-persona="#reasignar-persona" autocomplete="off" placeholder="Busca por nombre o documento…"
                               class="{{ $campo }} {{ $error('reasignar', 'person_id') ? 'border-red-400' : 'border-slate-200 focus:border-[#064C3D]' }}">
                        <input type="hidden" name="person_id" id="reasignar-persona">
                        @if ($error('reasignar', 'person_id')) <p class="mt-1 text-xs font-semibold text-red-600">{{ $error('reasignar', 'person_id') }}</p> @endif
                    </div>
                    <div>
                        <label for="reasignar-motivo" class="mb-1 block text-sm font-semibold text-slate-700">Motivo <span class="text-red-500">*</span></label>
                        <input id="reasignar-motivo" name="motivo" maxlength="200" value="{{ $errors->reasignar->any() ? old('motivo') : '' }}" placeholder="Por ejemplo: traslado a otro centro"
                               class="{{ $campo }} {{ $error('reasignar', 'motivo') ? 'border-red-400' : 'border-slate-200 focus:border-[#064C3D]' }}">
                        @if ($error('reasignar', 'motivo')) <p class="mt-1 text-xs font-semibold text-red-600">{{ $error('reasignar', 'motivo') }}</p> @endif
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" data-cerrar class="rounded-full px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancelar</button>
                    <button class="rounded-full bg-[#064C3D] px-5 py-2 text-sm font-semibold text-white hover:bg-[#043A2F]">Reasignar</button>
                </div>
            </form>
        </dialog>

        {{-- Retirar (HU-010, esc. 3 y 4) --}}
        <dialog id="modal-retirar" aria-labelledby="titulo-retirar" class="m-auto w-[min(94vw,30rem)] rounded-3xl p-0 shadow-2xl backdrop:bg-slate-900/50"
                @if ($errors->retirar->any()) data-abrir-al-cargar data-id="{{ old('designacion_id') }}" @endif>
            <form method="POST" data-accion="{{ route('talentohumano.lider.designaciones.retirar', '__ID__') }}" class="p-6" novalidate>
                @csrf @method('PATCH')
                <input type="hidden" name="designacion_id" data-campo-id value="{{ old('designacion_id') }}">
                <h2 id="titulo-retirar" class="text-lg font-bold text-slate-900">¿Retirar la designación?</h2>
                <p class="mt-1 text-sm text-slate-600"><span data-campo-nombre class="font-semibold"></span> dejará de ser <span data-campo-funcion></span>. Se conserva el historial.</p>
                <label for="retirar-motivo" class="mb-1 mt-4 block text-sm font-semibold text-slate-700">Motivo del retiro <span class="text-red-500">*</span></label>
                <input id="retirar-motivo" name="motivo" maxlength="200" value="{{ $errors->retirar->any() ? old('motivo') : '' }}" placeholder="Por ejemplo: fin de contrato"
                       class="{{ $campo }} {{ $error('retirar', 'motivo') ? 'border-red-400' : 'border-slate-200 focus:border-[#064C3D]' }}">
                @if ($error('retirar', 'motivo')) <p class="mt-1 text-xs font-semibold text-red-600">{{ $error('retirar', 'motivo') }}</p> @endif
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" data-cerrar class="rounded-full px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancelar</button>
                    <button class="rounded-full bg-red-600 px-5 py-2 text-sm font-semibold text-white hover:bg-red-700">Retirar</button>
                </div>
            </form>
        </dialog>

        {{-- Retiro bloqueado por pendientes (HU-010, esc. 1; RN-034) --}}
        @if ($bloqueo = session('pendientes'))
            <dialog id="modal-bloqueado" aria-labelledby="titulo-bloqueado" class="m-auto w-[min(94vw,30rem)] rounded-3xl p-0 shadow-2xl backdrop:bg-slate-900/50" data-abrir-al-cargar>
                <div class="p-6">
                    <span class="grid h-12 w-12 place-items-center rounded-2xl bg-amber-100 text-xl text-amber-600"><i class="fas fa-lock" aria-hidden="true"></i></span>
                    <h2 id="titulo-bloqueado" class="mt-4 text-lg font-bold text-slate-900">No se puede retirar todavía</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ $bloqueo['nombre'] }} tiene pendientes en un proceso de selección en curso. Reasigna la designación o espera a que los complete.</p>
                    <ul class="mt-4 space-y-2">
                        @foreach ($bloqueo['lista'] as $pendiente)
                            <li class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2 text-sm">
                                <span class="text-slate-700">{{ $pendiente['texto'] }}</span><span class="font-bold text-amber-700">{{ $pendiente['cantidad'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button" data-cerrar class="rounded-full px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">Entendido</button>
                    </div>
                </div>
            </dialog>
        @endif

        <datalist id="lista-instructores">
            @foreach ($instructores as $instructor)
                <option value="{{ $opcionInstructor($instructor) }}" data-id="{{ $instructor->id }}"></option>
            @endforeach
        </datalist>

        @push('scripts')
            <script>
                (() => {
                    const opciones = new Map([...document.querySelectorAll('#lista-instructores option')].map((o) => [o.value, o.dataset.id]));

                    // El texto elegido en la lista se convierte en el id de la persona
                    document.querySelectorAll('input[data-persona]').forEach((entrada) => {
                        const oculto = document.querySelector(entrada.dataset.persona);
                        entrada.addEventListener('input', () => { oculto.value = opciones.get(entrada.value) || ''; });
                    });
                })();
            </script>
        @endpush
    @endif
</x-talentohumano::layouts.lider>
