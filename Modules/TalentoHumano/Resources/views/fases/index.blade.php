@use('Modules\TalentoHumano\Http\Controllers\FaseController')
@php
    $claseCampo = 'mt-1 w-full rounded-xl border-0 px-3 py-2 text-sm ring-1 ring-slate-300 focus:ring-2 focus:ring-[#064C3D]';
@endphp
<x-talentohumano::layouts.master title="Fases">
    <x-talentohumano::encabezado titulo="Fases de SENA Empresa" :volver="route('talentohumano.panel')" hu="RF-018 · RF-001 · RF-003"
        subtitulo="Prepare la siguiente fase: seleccione las fichas que ingresan, active los cargos y elabore el cronograma del proceso de selección." />

    {{-- Errores del registro; los de cada fase se muestran en su formulario de edición --}}
    @if ($errors->registrar->any())
        <div class="mb-6 rounded-2xl bg-red-50 p-4 text-sm text-red-800 ring-1 ring-red-200" role="alert">
            <p class="font-semibold"><i class="fas fa-circle-exclamation mr-1" aria-hidden="true"></i> No se registró la fase:</p>
            <ul class="mt-2 list-disc space-y-0.5 pl-5">
                @foreach (array_unique($errors->registrar->all()) as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
        <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Fase</th>
                        <th class="px-4 py-3">Inicio</th>
                        <th class="px-4 py-3">Fin</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3">Fichas</th>
                        <th class="px-4 py-3">Cargos</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($fases as $fase)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-4 py-3 font-bold text-slate-900">Fase {{ $fase->numero }}</td>
                            <td class="px-4 py-3">{{ \Illuminate\Support\Carbon::parse($fase->fecha_inicio)->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">{{ $fase->fecha_fin ? \Illuminate\Support\Carbon::parse($fase->fecha_fin)->format('d/m/Y') : '—' }}</td>
                            <td class="px-4 py-3"><x-talentohumano::estado :valor="$fase->estado" :etiqueta="FaseController::ESTADOS[$fase->estado] ?? null" /></td>
                            <td class="px-4 py-3">{{ $fase->fichas }}</td>
                            <td class="px-4 py-3">{{ $fase->cargos }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('talentohumano.fases.show', $fase->id) }}" class="whitespace-nowrap rounded-full px-3 py-1.5 font-semibold text-[#064C3D] hover:bg-[#E8F3EF]">
                                        Abrir <i class="fas fa-arrow-right" aria-hidden="true"></i>
                                    </a>
                                    @if ($puedeGestionar)
                                        <button type="button" onclick="document.getElementById('editar-fase-{{ $fase->id }}').toggleAttribute('hidden')"
                                                class="grid h-8 w-8 place-items-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-[#064C3D]" title="Editar" aria-label="Editar la Fase {{ $fase->numero }}">
                                            <i class="fas fa-pen" aria-hidden="true"></i>
                                        </button>
                                        @if (empty($bloqueos[$fase->id]))
                                            <form method="POST" action="{{ route('talentohumano.fases.destroy', $fase->id) }}"
                                                  onsubmit="return confirm('¿Eliminar la Fase {{ $fase->numero }}? Esta acción no se puede deshacer.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="grid h-8 w-8 place-items-center rounded-lg text-slate-500 hover:bg-red-50 hover:text-red-600" title="Eliminar" aria-label="Eliminar la Fase {{ $fase->numero }}">
                                                    <i class="fas fa-trash" aria-hidden="true"></i>
                                                </button>
                                            </form>
                                        @else
                                            <span class="grid h-8 w-8 cursor-not-allowed place-items-center rounded-lg text-slate-300"
                                                  title="No se puede eliminar: ya tiene {{ implode(', ', $bloqueos[$fase->id]) }}">
                                                <i class="fas fa-trash" aria-hidden="true"></i>
                                            </span>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @if ($puedeGestionar)
                            @php $bolsa = $errors->getBag('editar_' . $fase->id); @endphp
                            <tr id="editar-fase-{{ $fase->id }}" class="bg-slate-50/70" @if ($bolsa->isEmpty()) hidden @endif>
                                <td colspan="7" class="px-4 py-4">
                                    <form method="POST" action="{{ route('talentohumano.fases.update', $fase->id) }}" class="grid gap-3 sm:grid-cols-[8rem_1fr_1fr_auto] sm:items-end">
                                        @csrf
                                        @method('PUT')
                                        <label class="block">
                                            <span class="text-xs font-semibold text-slate-700">Número</span>
                                            <input type="number" name="numero" min="1" required value="{{ $bolsa->any() ? old('numero') : $fase->numero }}" class="{{ $claseCampo }} {{ $bolsa->has('numero') ? 'ring-red-400' : '' }}">
                                        </label>
                                        <label class="block">
                                            <span class="text-xs font-semibold text-slate-700">Fecha de inicio</span>
                                            <input type="date" name="fecha_inicio" required value="{{ $bolsa->any() ? old('fecha_inicio') : $fase->fecha_inicio }}" min="{{ min(today(), \Illuminate\Support\Carbon::parse($fase->fecha_inicio)->startOfDay())->format('Y-m-d') }}" class="{{ $claseCampo }} {{ $bolsa->has('fecha_inicio') ? 'ring-red-400' : '' }}">
                                        </label>
                                        <label class="block">
                                            <span class="text-xs font-semibold text-slate-700">Fecha de fin</span>
                                            <input type="date" name="fecha_fin" required value="{{ $bolsa->any() ? old('fecha_fin') : $fase->fecha_fin }}" min="{{ ($fase->fecha_fin ? min(today(), \Illuminate\Support\Carbon::parse($fase->fecha_fin)->startOfDay()) : today())->format('Y-m-d') }}" class="{{ $claseCampo }} {{ $bolsa->has('fecha_fin') ? 'ring-red-400' : '' }}">
                                        </label>
                                        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-full bg-[#064C3D] px-4 py-2 text-sm font-semibold text-white hover:bg-[#043A2F]">
                                            <i class="fas fa-floppy-disk" aria-hidden="true"></i> Guardar cambios
                                        </button>
                                    </form>
                                    @if ($bolsa->any())
                                        <ul class="mt-2 list-disc pl-5 text-xs font-semibold text-red-600" role="alert">
                                            @foreach (array_unique($bolsa->all()) as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-slate-500">Aún no hay fases registradas. Registre la siguiente fase para empezar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($puedeGestionar)
        <form method="POST" action="{{ route('talentohumano.fases.store') }}" class="h-fit rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            @csrf
            <h2 class="font-bold text-[#064C3D]">Registrar fase</h2>
            <p class="mt-1 text-xs text-slate-500">Las fases se identifican por su número (por ejemplo, la fase 49). Sus fechas no pueden cruzarse con las de otra fase.</p>

            <label class="mt-4 block">
                <span class="text-sm font-semibold text-slate-700">Número de la fase</span>
                <input type="number" name="numero" value="{{ old('numero', $siguienteNumero) }}" min="1" required class="{{ $claseCampo }}">
            </label>
            <label class="mt-3 block">
                <span class="text-sm font-semibold text-slate-700">Fecha de inicio</span>
                <input type="date" name="fecha_inicio" value="{{ old('fecha_inicio') }}" min="{{ today()->format('Y-m-d') }}" required class="{{ $claseCampo }}">
            </label>
            <label class="mt-3 block">
                <span class="text-sm font-semibold text-slate-700">Fecha de fin</span>
                <input type="date" name="fecha_fin" value="{{ old('fecha_fin') }}" min="{{ today()->format('Y-m-d') }}" required class="{{ $claseCampo }}">
            </label>

            <button type="submit" class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-full bg-[#064C3D] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#043A2F]">
                <i class="fas fa-plus" aria-hidden="true"></i> Registrar fase
            </button>
        </form>
        @endif
    </div>
</x-talentohumano::layouts.master>
