@use('Modules\TalentoHumano\Support\CargaMasiva')
@php
    $paso = $resultado ? 3 : ($revision ? 2 : 1);
    $campo = 'w-full rounded-xl border bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#34D399]/40';
    $tarjeta = 'rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70';
    $pasos = [1 => 'Descargar y subir la plantilla', 2 => 'Revisar las filas', 3 => 'Confirmar la carga'];
    $doc = fn ($d) => is_numeric($d) ? number_format((float) $d, 0, ',', '.') : $d;
@endphp

<x-talentohumano::layouts.lider title="Carga masiva de asignaciones" activo="asignaciones">
    <a href="{{ route('talentohumano.lider.asignaciones.index') }}" class="text-sm font-semibold text-emerald-700 hover:underline">
        <i class="fas fa-arrow-left mr-1" aria-hidden="true"></i> Asignaciones
    </a>
    <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">Carga masiva de asignaciones</h1>
    <p class="mt-1 text-sm text-slate-600">Carga de una vez a todos los aprendices que ocupan cargos en la fase en curso. Nada se guarda hasta que confirmes.</p>

    {{-- Pasos --}}
    <ol class="mt-6 grid gap-2 sm:grid-cols-3">
        @foreach ($pasos as $numero => $texto)
            <li @class(['flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-semibold ring-1', 'bg-[#064C3D] text-white ring-[#064C3D]' => $numero === $paso,
                        'bg-[#E8F3EF] text-[#064C3D] ring-[#E8F3EF]' => $numero < $paso, 'bg-white text-slate-400 ring-slate-200' => $numero > $paso])>
                <span @class(['grid h-7 w-7 shrink-0 place-items-center rounded-full text-xs', 'bg-[#34D399] text-[#043A2F]' => $numero === $paso, 'bg-[#064C3D] text-white' => $numero < $paso, 'bg-slate-100' => $numero > $paso])>
                    @if ($numero < $paso) <i class="fas fa-check" aria-hidden="true"></i> @else {{ $numero }} @endif
                </span>
                {{ $texto }}
            </li>
        @endforeach
    </ol>

    @if (!$fase && $paso < 3)
        <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <i class="fas fa-triangle-exclamation mr-1 text-amber-500" aria-hidden="true"></i>
            No hay una fase en curso. El Gestor de Talento Humano debe registrarla primero desde «Fases».
        </div>
    @elseif ($paso === 1)
        <div class="mt-6 grid gap-4 xl:grid-cols-2">
            <section class="{{ $tarjeta }}">
                <h2 class="font-bold text-[#064C3D]">Descarga la plantilla</h2>
                <p class="mt-1 text-sm text-slate-600">Diligencia una fila por aprendiz en la hoja «Asignaciones», con estas columnas:</p>
                <div class="mt-3 overflow-x-auto rounded-2xl ring-1 ring-slate-200">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-[#064C3D] text-white"><tr>@foreach (CargaMasiva::COLUMNAS as $columna)<th class="px-3 py-2 font-semibold">{{ $columna }}</th>@endforeach</tr></thead>
                        <tbody class="text-slate-500"><tr><td class="px-3 py-2">1075276540</td><td class="px-3 py-2">Gestor de Talento Humano</td><td class="px-3 py-2">04/08/2026</td><td class="px-3 py-2">Punto de venta</td></tr></tbody>
                    </table>
                </div>
                <p class="mt-3 text-xs text-slate-500">La plantilla incluye hojas con los nombres exactos de los cargos activos del catálogo y de las unidades productivas. La unidad productiva es opcional.</p>
                <a href="{{ route('talentohumano.lider.asignaciones.plantilla') }}" class="mt-4 inline-flex items-center gap-2 rounded-full bg-[#E8F3EF] px-5 py-2.5 text-sm font-semibold text-[#064C3D] hover:bg-emerald-100">
                    <i class="fas fa-download" aria-hidden="true"></i> Descargar plantilla (.xlsx)
                </a>
            </section>

            <form method="POST" action="{{ route('talentohumano.lider.asignaciones.validar') }}" enctype="multipart/form-data" class="{{ $tarjeta }}">
                @csrf
                <h2 class="font-bold text-[#064C3D]">Sube el archivo diligenciado</h2>
                <label for="archivo" @class(['mt-4 flex cursor-pointer flex-col items-center gap-2 rounded-2xl border-2 border-dashed px-4 py-8 text-center transition hover:border-[#064C3D] hover:bg-[#E8F3EF]/40',
                                             'border-red-300 bg-red-50/50' => $errors->has('archivo'), 'border-slate-300' => !$errors->has('archivo')])>
                    <i class="fas fa-file-arrow-up text-3xl text-[#064C3D]" aria-hidden="true"></i>
                    <span class="text-sm font-semibold text-slate-700" data-nombre-archivo>Haz clic para elegir el archivo</span>
                    <span class="text-xs text-slate-500">Solo .xlsx · máximo 5 MB</span>
                    <input id="archivo" name="archivo" type="file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" class="sr-only"
                           onchange="this.closest('label').querySelector('[data-nombre-archivo]').textContent = this.files[0]?.name || 'Haz clic para elegir el archivo'">
                </label>
                @error('archivo')
                    <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p>
                    @if ($message === CargaMasiva::MENSAJE_FORMATO)
                        <a href="{{ route('talentohumano.lider.asignaciones.plantilla') }}" class="text-sm font-semibold text-emerald-700 hover:underline">Descargar la plantilla de carga inicial</a>
                    @endif
                @enderror
                <div class="mt-4 flex items-center justify-between gap-3">
                    <p class="text-sm text-slate-600">Fase: <strong class="text-[#064C3D]">{{ $fase->nombre() }} · En curso</strong></p>
                    <button class="rounded-full bg-[#064C3D] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#043A2F]"><i class="fas fa-magnifying-glass mr-1" aria-hidden="true"></i> Validar archivo</button>
                </div>
            </form>
        </div>
    @elseif ($paso === 2)
        @php $correctas = count($revision['correctas']); $conError = count($revision['errores']); @endphp
        <div class="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-white px-4 py-3 text-sm shadow-sm ring-1 ring-slate-200/70">
            <span class="text-slate-600"><i class="fas fa-file-excel mr-1 text-emerald-600" aria-hidden="true"></i><strong>{{ $revision['archivo'] }}</strong> · {{ $correctas + $conError }} filas · validado el {{ \Illuminate\Support\Carbon::parse($revision['validado'])->format('d/m/Y g:i a') }}</span>
            <form method="POST" action="{{ route('talentohumano.lider.asignaciones.cancelar') }}">@csrf<button class="text-sm font-semibold text-emerald-700 hover:underline">Cambiar archivo</button></form>
        </div>

        <div class="mt-4 grid gap-3 sm:grid-cols-3">
            <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70"><p class="text-xs text-slate-500">Filas en el archivo</p><p class="text-2xl font-extrabold text-[#064C3D]">{{ $correctas + $conError }}</p></div>
            <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70"><p class="text-xs text-slate-500">Correctas</p><p class="text-2xl font-extrabold text-emerald-600">{{ $correctas }}</p></div>
            <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70"><p class="text-xs text-slate-500">Con error</p><p class="text-2xl font-extrabold {{ $conError ? 'text-red-600' : 'text-slate-400' }}">{{ $conError }}</p></div>
        </div>

        @if ($conError)
            <section class="mt-4 overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-red-200">
                <h2 class="bg-red-50 px-4 py-3 text-sm font-bold text-red-700"><i class="fas fa-circle-xmark mr-1" aria-hidden="true"></i> Filas con error ({{ $conError }}): no se guardarán</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th class="px-4 py-2 font-semibold">Fila</th><th class="px-4 py-2 font-semibold">Documento</th><th class="px-4 py-2 font-semibold">Cargo</th><th class="px-4 py-2 font-semibold">Motivo del error</th></tr></thead>
                        <tbody class="divide-y divide-slate-100 text-slate-600">
                            @foreach ($revision['errores'] as $error)
                                <tr><td class="px-4 py-2">{{ $error['fila'] }}</td><td class="px-4 py-2">{{ $doc($error['documento']) }}</td><td class="px-4 py-2">{{ $error['cargo'] }}</td><td class="px-4 py-2 font-medium text-red-700">{{ $error['motivo'] }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        @if ($correctas)
            <section class="mt-4 overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200/70">
                <h2 class="bg-[#E8F3EF] px-4 py-3 text-sm font-bold text-[#064C3D]"><i class="fas fa-circle-check mr-1" aria-hidden="true"></i> Filas correctas ({{ $correctas }})</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th class="px-4 py-2 font-semibold">Fila</th><th class="px-4 py-2 font-semibold">Aprendiz</th><th class="px-4 py-2 font-semibold">Cargo</th><th class="px-4 py-2 font-semibold">Inicio</th></tr></thead>
                        <tbody class="divide-y divide-slate-100 text-slate-600">
                            @foreach (array_slice($revision['correctas'], 0, 15) as $fila)
                                <tr><td class="px-4 py-2">{{ $fila['fila'] }}</td><td class="px-4 py-2"><span class="font-semibold text-slate-800">{{ $fila['nombre'] }}</span> <span class="text-xs text-slate-400">CC {{ $doc($fila['documento']) }}</span></td><td class="px-4 py-2">{{ $fila['cargo'] }}</td><td class="px-4 py-2">{{ \Illuminate\Support\Carbon::parse($fila['fecha_inicio'])->format('d/m/Y') }}</td></tr>
                            @endforeach
                            @if ($correctas > 15) <tr><td colspan="4" class="px-4 py-2 text-slate-400">… y {{ $correctas - 15 }} filas más</td></tr> @endif
                        </tbody>
                    </table>
                </div>
            </section>

            <form method="POST" action="{{ route('talentohumano.lider.asignaciones.confirmar') }}" novalidate class="mt-4 {{ $tarjeta }}" data-titulo="¿Confirmar la carga?"
                  data-confirmar="Se crearán {{ $correctas }} asignaciones en estado «vigente» con origen «carga inicial».{{ $conError ? " Las {$conError} filas con error no se guardarán." : '' }}">
                @csrf
                <h2 class="font-bold text-[#064C3D]">Confirmar la carga</h2>
                <label for="justificacion" class="mb-1 mt-4 block text-sm font-semibold text-slate-700">Justificación para toda la carga <span class="text-red-500">*</span></label>
                <textarea id="justificacion" name="justificacion" rows="3" maxlength="2000" placeholder="Por ejemplo: puesta en marcha de FOLIUM con la fase en curso."
                          class="{{ $campo }} {{ $errors->has('justificacion') ? 'border-red-400' : 'border-slate-200 focus:border-[#064C3D]' }}">{{ old('justificacion') }}</textarea>
                @error('justificacion') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-xs text-slate-500">Puedes corregir las filas con error y subirlas después en otra carga.</p>
                    <button class="rounded-full bg-[#064C3D] px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#064C3D]/20 hover:bg-[#043A2F]"><i class="fas fa-check mr-1" aria-hidden="true"></i> Crear {{ $correctas }} asignaciones</button>
                </div>
            </form>
        @else
            <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                Ninguna fila es correcta. Corrige el archivo y vuelve a subirlo con «Cambiar archivo».
            </div>
        @endif
    @else
        @php $gestores = $resultado->asignaciones->filter->esGestorTH()->count(); @endphp
        <section class="relative isolate mt-6 overflow-hidden rounded-3xl bg-[#064C3D] p-8 text-white">
            <span class="grid h-14 w-14 place-items-center rounded-2xl bg-white/10 text-2xl text-[#34D399] ring-1 ring-white/20"><i class="fas fa-circle-check" aria-hidden="true"></i></span>
            <h2 class="mt-4 text-2xl font-extrabold">{{ $resultado->filas_correctas }} asignaciones creadas</h2>
            <p class="mt-1 max-w-2xl text-emerald-50/85">
                Quedaron en estado «vigente» con origen «carga inicial».
                @if ($gestores) {{ $gestores === 1 ? 'El Gestor de Talento Humano ya tiene su rol.' : "Los {$gestores} Gestores de Talento Humano ya tienen su rol." }} @endif
                La carga quedó registrada en la auditoría.
            </p>
            <div class="mt-5 flex flex-wrap gap-2">
                <a href="{{ route('talentohumano.lider.asignaciones.index') }}" class="rounded-full bg-[#34D399] px-5 py-2.5 text-sm font-semibold text-[#043A2F] hover:bg-emerald-300">Ver asignaciones</a>
                <a href="{{ route('talentohumano.lider.asignaciones.carga-masiva') }}" class="rounded-full px-5 py-2.5 text-sm font-semibold ring-1 ring-white/30 hover:bg-white/10">Hacer otra carga</a>
            </div>
        </section>

        @if ($resultado->filas_con_error)
            <section class="mt-4 {{ $tarjeta }}">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="font-bold text-red-700">Filas rechazadas para corregir ({{ $resultado->filas_con_error }})</h2>
                    <a href="{{ route('talentohumano.lider.asignaciones.rechazadas', $resultado) }}" class="rounded-full bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-100"><i class="fas fa-download mr-1" aria-hidden="true"></i> Descargar filas rechazadas</a>
                </div>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($resultado->errores as $error)
                        <li class="rounded-xl bg-slate-50 px-3 py-2"><strong>Fila {{ $error['fila'] }}</strong> · {{ $doc($error['documento']) }} · {{ $error['cargo'] }} <span class="block text-xs text-red-700">{{ $error['motivo'] }}</span></li>
                    @endforeach
                </ul>
            </section>
        @endif
    @endif
</x-talentohumano::layouts.lider>
