<x-talentohumano::layouts.aprendiz title="Mi Empalme y Entrega" activo="empalme" :estado="$estado" :estados="$estados">
    <div class="mx-auto max-w-3xl">
        
        {{-- Encabezado --}}
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">Proceso de Empalme</h1>
                <p class="mt-1 text-sm text-slate-600">
                    Líder de Unidad de Formación Productiva Cacao · Empalme con <strong>María José Ospina</strong> (saliente).
                </p>
            </div>
            <span class="rounded-full bg-[#E8F3EF] px-3.5 py-1 text-xs font-bold text-[#064C3D]">
                Fase XXXVI · Área Agrícola
            </span>
        </div>

        {{-- Barra de Progreso del Empalme (RF-013) --}}
        <div class="mt-6 rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Etapas del Empalme</p>
            <ol class="grid gap-2 grid-cols-2 sm:grid-cols-4 text-xs font-semibold">
                @php
                    $pasosEmp = [
                        ['Inventario', in_array($estado, ['entrante', 'saliente', 'saliente-error']), in_array($estado, ['visita', 'firmar', 'instructor'])],
                        ['Visita a la Unidad', $estado === 'visita', in_array($estado, ['firmar', 'instructor'])],
                        ['Firmas del Acta', in_array($estado, ['firmar', 'instructor']), false],
                        ['Satisfacción', false, false],
                    ];
                @endphp
                @foreach ($pasosEmp as $pIdx => [$nomP, $esAct, $esDone])
                    <li @class([
                        'flex items-center gap-2 rounded-2xl p-2.5',
                        'bg-[#064C3D] text-white shadow-sm' => $esAct,
                        'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200' => $esDone,
                        'bg-slate-50 text-slate-400' => !$esAct && !$esDone,
                    ])>
                        <span @class([
                            'grid h-5 w-5 place-items-center rounded-full text-[10px] font-bold shrink-0',
                            'bg-[#34D399] text-[#043A2F]' => $esAct,
                            'bg-emerald-600 text-white' => $esDone,
                            'bg-slate-200 text-slate-500' => !$esAct && !$esDone,
                        ])>
                            @if ($esDone) <i class="fas fa-check"></i> @else {{ $pIdx + 1 }} @endif
                        </span>
                        <span class="truncate">{{ $nomP }}</span>
                    </li>
                @endforeach
            </ol>
        </div>

        {{-- Sección 1: Inventario del Cargo (HU-052, HU-053) --}}
        @if (in_array($estado, ['entrante', 'saliente', 'saliente-error']))
            <section class="entrada mt-6 rounded-3xl bg-white p-5 sm:p-6 shadow-sm ring-1 ring-slate-200/70">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="font-bold text-[#064C3D] flex items-center gap-2">
                            <i class="fas fa-boxes-stacked text-emerald-600"></i>
                            {{ $estado === 'entrante' ? 'Verificación de Inventario Recibido' : 'Registro de Inventario Real a Entregar' }}
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            {{ $estado === 'entrante' 
                                ? 'Revisa cada implemento y su estado físico antes de confirmar el recibimiento.' 
                                : 'Registra los implementos que dejas bajo custodia del aprendiz entrante.' }}
                        </p>
                    </div>
                </div>

                @if ($estado === 'saliente-error')
                    <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 p-4 text-xs font-bold text-red-800 flex items-center gap-2" role="alert">
                        <i class="fas fa-circle-exclamation text-red-600 text-base"></i>
                        <span>Un implemento agregado no tiene cantidad ni estado definido. Completa los datos requeridos.</span>
                    </div>
                @endif

                <form action="{{ route('talentohumano.aprendiz.empalme.inventario') }}" method="POST" class="mt-5 space-y-3">
                    @csrf
                    
                    <div class="space-y-2.5" id="contenedor-items">
                        @foreach ($inventario as $idx => $inv)
                            <div class="rounded-2xl p-3.5 ring-1 ring-slate-200 bg-slate-50/50 flex flex-wrap items-center justify-between gap-3 text-xs sm:text-sm">
                                <div class="flex-1 min-w-[180px]">
                                    <p class="font-bold text-slate-800">{{ $inv['item'] }}</p>
                                    @if ($inv['novedad'])
                                        <p class="text-[11px] text-amber-700 mt-0.5">
                                            <i class="fas fa-triangle-exclamation mr-1"></i> {{ $inv['novedad'] }}
                                        </p>
                                    @endif
                                </div>

                                <div class="flex items-center gap-3">
                                    <span class="rounded-full bg-white px-3 py-1 font-semibold text-slate-700 ring-1 ring-slate-200">
                                        Cant: {{ $inv['cantidad'] }}
                                    </span>
                                    <span @class([
                                        'rounded-full px-2.5 py-0.5 text-xs font-bold',
                                        'bg-emerald-100 text-emerald-800' => $inv['estado'] === 'Bueno',
                                        'bg-amber-100 text-amber-800' => $inv['estado'] === 'Regular',
                                        'bg-red-100 text-red-800' => $inv['estado'] === 'Malo',
                                    ])>
                                        {{ $inv['estado'] }}
                                    </span>
                                    @if ($estado === 'entrante')
                                        <label class="flex items-center gap-1.5 cursor-pointer ml-2">
                                            <input type="checkbox" checked class="h-4 w-4 rounded-sm text-[#064C3D] accent-[#064C3D]">
                                            <span class="text-xs font-semibold text-slate-600">Conforme</span>
                                        </label>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
                        <button type="button" onclick="window.mostrarAviso('Implemento adicional agregado.', 'info')" class="inline-flex items-center gap-1.5 rounded-full px-4 py-2 text-xs font-bold text-[#064C3D] ring-1 ring-[#064C3D]/30 hover:bg-[#E8F3EF] transition">
                            <i class="fas fa-plus"></i> Agregar implemento
                        </button>
                        <button type="submit" class="rounded-full bg-[#064C3D] px-6 py-2.5 text-xs font-bold text-white hover:bg-[#043A2F] transition shadow-md">
                            Guardar inventario y continuar a visita <i class="fas fa-arrow-right ml-1"></i>
                        </button>
                    </div>
                </form>
            </section>
        @endif

        {{-- Sección 2: Visita a la Unidad Productiva (HU-057) --}}
        @if ($estado === 'visita')
            <section class="entrada mt-6 rounded-3xl bg-white p-5 sm:p-6 shadow-sm ring-1 ring-slate-200/70">
                <div class="border-b border-slate-100 pb-3">
                    <h2 class="font-bold text-[#064C3D] flex items-center gap-2">
                        <i class="fas fa-location-dot text-emerald-600"></i> Visita a la Unidad Productiva
                    </h2>
                    <p class="mt-1 text-xs text-slate-500">
                        Prerrequisito obligatorio según la plantilla del área (HU-057): Realicen juntos el recorrido de la Unidad Productiva Cacao antes de la firma digital.
                    </p>
                </div>

                <form action="{{ route('talentohumano.aprendiz.empalme.visita') }}" method="POST" class="mt-5 space-y-4">
                    @csrf
                    <div>
                        <label for="fecha_visita" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                            Fecha de realización de la visita <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="fecha_visita" id="fecha_visita" required value="{{ date('Y-m-d') }}" class="w-full sm:w-64 rounded-2xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm focus:border-[#064C3D] focus:outline-none focus:ring-2 focus:ring-[#34D399]/40 text-slate-800">
                    </div>

                    <div>
                        <p class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            Participantes presentes en la visita <span class="text-red-500">*</span>
                        </p>
                        <div class="space-y-2">
                            @foreach ($participantes as $p)
                                <label class="flex items-center gap-3 rounded-2xl p-3 ring-1 ring-slate-200 bg-slate-50/50 hover:bg-[#E8F3EF]/40 transition cursor-pointer">
                                    <input type="checkbox" name="participantes[]" value="{{ $p['nombre'] }}" @checked($p['marcado']) class="h-4 w-4 rounded-sm text-[#064C3D] accent-[#064C3D]">
                                    <div class="text-xs">
                                        <span class="font-bold text-slate-800">{{ $p['nombre'] }}</span>
                                        <span class="text-slate-400 ml-1">({{ $p['rol'] }})</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="rounded-full bg-[#064C3D] px-6 py-2.5 text-xs font-bold text-white hover:bg-[#043A2F] transition shadow-md">
                            <i class="fas fa-check mr-1.5"></i> Confirmar visita y habilitar firmas
                        </button>
                    </div>
                </form>
            </section>
        @endif

        {{-- Sección 3: Firma Digital del Acta de Recibimiento (HU-052, HU-057) --}}
        @if (in_array($estado, ['firmar', 'instructor']))
            <section class="entrada mt-6 rounded-3xl bg-white p-5 sm:p-6 shadow-sm ring-1 ring-slate-200/70">
                <div class="border-b border-slate-100 pb-3">
                    <h2 class="font-bold text-[#064C3D] flex items-center gap-2">
                        <i class="fas fa-signature text-emerald-600"></i> Firmas Digitales del Acta
                    </h2>
                    <p class="mt-1 text-xs text-slate-500">
                        El acta de recibimiento requiere la firma digital de las tres partes involucradas.
                    </p>
                </div>

                @if ($estado === 'instructor')
                    <div class="mt-4 rounded-2xl bg-sky-50 border border-sky-200 p-4 text-xs font-medium text-sky-900 flex items-start gap-2.5">
                        <i class="fas fa-hourglass-half text-sky-600 text-sm mt-0.5"></i>
                        <span>
                            Ustedes ya firmaron digitalmente. El acta permanece en estado <strong>«Pendiente de firmas»</strong> hasta que el instructor <strong>Jorge Iván Cuéllar</strong> ingrese su firma. Le hemos enviado una notificación por correo.
                        </span>
                    </div>
                @endif

                {{-- Estado de Firmantes --}}
                <div class="mt-5 space-y-2.5">
                    @foreach ($firmas as $f)
                        <div class="flex items-center justify-between rounded-2xl p-3.5 ring-1 ring-slate-200 bg-slate-50/50 text-xs sm:text-sm">
                            <div class="flex items-center gap-2.5">
                                @if ($f['firmado'])
                                    <i class="fas fa-circle-check text-emerald-600 text-base"></i>
                                @else
                                    <i class="far fa-circle text-slate-300 text-base"></i>
                                @endif
                                <div>
                                    <p class="font-bold text-slate-800">{{ $f['nombre'] }}</p>
                                    <p class="text-[11px] text-slate-400">{{ $f['rol'] }}</p>
                                </div>
                            </div>
                            <div>
                                @if ($f['firmado'])
                                    <span class="firma text-base font-bold text-[#064C3D]">{{ explode(' ', $f['nombre'])[0] }}</span>
                                    <span class="text-[10px] text-slate-400 ml-2 hidden sm:inline">{{ $f['fecha'] }}</span>
                                @else
                                    <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-[10px] font-bold text-amber-800">
                                        Pendiente
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($estado === 'firmar')
                    {{-- Cuadro de Firma Táctil / Digital --}}
                    <form action="{{ route('talentohumano.aprendiz.empalme.firmar') }}" method="POST" class="mt-6 border-t border-slate-100 pt-5">
                        @csrf
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            Tu Firma Digital (Santiago Rojas Polanía)
                        </label>
                        <div class="h-32 rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 flex flex-col items-center justify-center text-slate-400 text-xs hover:border-[#064C3D] transition cursor-crosshair">
                            <i class="fas fa-pen-nib text-lg mb-1 text-slate-300"></i>
                            <span>Firma aquí con el dedo o el mouse</span>
                            <span class="text-[10px] text-slate-300 mt-1">Hash criptográfico verificado con cuenta SICEFA</span>
                        </div>

                        <label class="mt-4 flex items-start gap-2.5 text-xs text-slate-600 cursor-pointer">
                            <input type="checkbox" required class="h-4 w-4 rounded-sm text-[#064C3D] accent-[#064C3D] mt-0.5">
                            <span>Confirmo que verifiqué el inventario físico, realicé la visita técnica a la unidad productiva y acepto la entrega del cargo.</span>
                        </label>

                        <div class="mt-5 flex justify-end">
                            <button type="submit" class="rounded-full bg-[#064C3D] px-8 py-3 text-xs font-bold text-white hover:bg-[#043A2F] transition shadow-lg shadow-[#064C3D]/20">
                                <i class="fas fa-signature mr-1.5"></i> Registrar mi firma digital
                            </button>
                        </div>
                    </form>
                @endif
            </section>
        @endif

    </div>
</x-talentohumano::layouts.aprendiz>
