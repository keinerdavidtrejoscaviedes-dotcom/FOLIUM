@use('Modules\TalentoHumano\Http\Controllers\FaseController')
@php
    $claseCampo = 'mt-1 w-full rounded-xl border-0 px-3 py-2 text-sm ring-1 ring-slate-300 focus:ring-2 focus:ring-[#064C3D]';
@endphp
<x-talentohumano::layouts.master title="Fases">
    <x-talentohumano::encabezado titulo="Fases de SENA Empresa" :volver="route('talentohumano.panel')" hu="RF-018 · RF-001 · RF-003"
        subtitulo="Prepare la siguiente fase: seleccione las fichas que ingresan, active los cargos y elabore el cronograma del proceso de selección." />

    <x-talentohumano::errores />

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
                                <a href="{{ route('talentohumano.fases.show', $fase->id) }}" class="whitespace-nowrap rounded-full px-3 py-1.5 font-semibold text-[#064C3D] hover:bg-[#E8F3EF]">
                                    Abrir <i class="fas fa-arrow-right" aria-hidden="true"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-slate-500">Aún no hay fases registradas. Registre la siguiente fase para empezar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <form method="POST" action="{{ route('talentohumano.fases.store') }}" class="h-fit rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            @csrf
            <h2 class="font-bold text-[#064C3D]">Registrar fase</h2>
            <p class="mt-1 text-xs text-slate-500">Las fases se identifican por su número (por ejemplo, la fase 49).</p>

            <label class="mt-4 block">
                <span class="text-sm font-semibold text-slate-700">Número de la fase</span>
                <input type="number" name="numero" value="{{ old('numero', $siguienteNumero) }}" min="1" required class="{{ $claseCampo }}">
            </label>
            <label class="mt-3 block">
                <span class="text-sm font-semibold text-slate-700">Fecha de inicio</span>
                <input type="date" name="fecha_inicio" value="{{ old('fecha_inicio') }}" required class="{{ $claseCampo }}">
            </label>
            <label class="mt-3 block">
                <span class="text-sm font-semibold text-slate-700">Fecha de fin <span class="font-normal text-slate-400">(opcional)</span></span>
                <input type="date" name="fecha_fin" value="{{ old('fecha_fin') }}" class="{{ $claseCampo }}">
            </label>

            <button type="submit" class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-full bg-[#064C3D] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#043A2F]">
                <i class="fas fa-plus" aria-hidden="true"></i> Registrar fase
            </button>
        </form>
    </div>
</x-talentohumano::layouts.master>
