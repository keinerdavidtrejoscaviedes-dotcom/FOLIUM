@use('Modules\TalentoHumano\Http\Controllers\ConvocatoriaController')
@php
    $persona = fn ($p) => $p ? trim($p->first_name . ' ' . $p->first_last_name) : 'Sin registro';
    $punto = ['hecho' => 'bg-[#064C3D] ring-[#E8F3EF]', 'actual' => 'bg-amber-400 ring-amber-100', 'pendiente' => 'bg-slate-300 ring-white'];
    $color = ['amber' => 'border-amber-200 bg-amber-50 text-amber-900', 'red' => 'border-red-200 bg-red-50 text-red-800'];
    $estadoConvocatoria = ['borrador' => 'bg-slate-100 text-slate-600', 'publicada' => 'bg-emerald-100 text-emerald-800', 'cerrada' => 'bg-slate-100 text-slate-600', 'finalizada' => 'bg-slate-100 text-slate-600'];
    $programas = $fichas->pluck('programa', 'program_id');
    $excepciones = $convocatoria?->convocatoriaCargos->filter(fn ($cc) => !empty($cc->programas_extra)) ?? collect();
@endphp

<x-talentohumano::layouts.lider title="Supervisión · Selección" activo="supervision">
    @include('talentohumano::lider.supervision._encabezado', ['actual' => 'index'])

    @if (!$fase)
        <div class="mt-6 rounded-3xl bg-white px-6 py-14 text-center shadow-sm ring-1 ring-slate-200/70">
            <i class="fas fa-calendar-xmark mb-2 block text-3xl text-slate-300" aria-hidden="true"></i>
            <p class="font-semibold text-slate-700">No hay una fase en curso</p>
            <p class="mt-1 text-sm text-slate-500">Las fases anteriores se consultan en «Actas e historial».</p>
        </div>
    @else
        {{-- Bloqueos de la secuencia del proceso (HU-014, esc. 2) --}}
        @if ($bloqueos)
            <section class="mt-4 space-y-2" role="alert">
                @foreach ($bloqueos as [$icono, $tono, $titulo, $texto])
                    <div class="flex items-start gap-3 rounded-2xl border px-4 py-3 text-sm {{ $color[$tono] }}">
                        <i class="fas {{ $icono }} mt-0.5" aria-hidden="true"></i>
                        <div class="min-w-0 flex-1"><p class="font-semibold">{{ $titulo }}</p><p class="mt-0.5">{{ $texto }}</p></div>
                    </div>
                @endforeach
            </section>
        @endif

        @if (!$convocatoria && !$hayEmpalmes)
            {{-- HU-014, esc. 5 --}}
            <div class="mt-4 rounded-3xl bg-white px-6 py-14 text-center shadow-sm ring-1 ring-slate-200/70">
                <i class="fas fa-hourglass-start mb-2 block text-3xl text-slate-300" aria-hidden="true"></i>
                <p class="font-semibold text-slate-700">No hay un proceso de selección ni empalmes en curso en esta fase</p>
                <p class="mt-1 text-sm text-slate-500">La supervisión se llena cuando el Gestor de Talento Humano cree la convocatoria de la {{ $fase->nombre_completo }}.</p>
            </div>
        @endif

        <div class="mt-4 grid gap-4 xl:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="min-w-0 space-y-4">
                @if ($cronograma)
                    <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h2 class="font-bold text-[#064C3D]">{{ $cronograma->estado === 'aprobado' ? 'Cronograma aprobado' : 'Cronograma' }}</h2>
                            @if ($cronograma->estado === 'aprobado')
                                <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800"><i class="fas fa-check mr-1" aria-hidden="true"></i>Aprobado el {{ $cronograma->fecha_aprobacion?->format('d/m/Y') }} por {{ $persona($cronograma->aprobadoPor) }}</span>
                            @else
                                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">{{ ['borrador' => 'Borrador', 'enviado' => 'En aprobación', 'rechazado' => 'Rechazado'][$cronograma->estado] }}</span>
                            @endif
                        </div>
                        <ol class="relative mt-5 space-y-4 border-l-2 border-[#E8F3EF] pl-6">
                            @foreach ($etapas as $etapa)
                                <li class="relative">
                                    <span class="absolute -left-[31px] top-1 h-3.5 w-3.5 rounded-full ring-4 {{ $punto[$etapa['situacion']] }}" aria-hidden="true"></span>
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <p class="text-sm font-semibold {{ $etapa['situacion'] === 'pendiente' ? 'text-slate-500' : 'text-slate-800' }}">{{ $etapa['nombre'] }}</p>
                                        <span class="text-xs {{ $etapa['situacion'] === 'actual' ? 'font-semibold text-amber-700' : 'text-slate-500' }}">{{ $etapa['fechas'] }}{{ $etapa['situacion'] === 'actual' ? ' · en curso' : '' }}</span>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endif

                {{-- HU-014, esc. 1: la convocatoria completa, sin botones de edición ni de publicación --}}
                @if ($convocatoria)
                    <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h2 class="font-bold text-[#064C3D]">{{ $convocatoria->titulo }}</h2>
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $estadoConvocatoria[$convocatoria->estado] }}">{{ ConvocatoriaController::ESTADOS[$convocatoria->estado] }}</span>
                        </div>
                        <p class="mt-1 text-sm text-slate-500">
                            Del {{ $convocatoria->fecha_apertura?->format('d/m/Y') ?? '—' }} al {{ $convocatoria->fecha_cierre?->format('d/m/Y') ?? '—' }}
                            @if ($convocatoria->publicadaPor) · publicada por {{ $persona($convocatoria->publicadaPor) }} @endif
                            · umbral {{ rtrim(rtrim((string) $convocatoria->umbral, '0'), '.') }} puntos · sin aptos: {{ mb_strtolower(ConvocatoriaController::POLITICAS[$convocatoria->politica_sin_aptos] ?? '—') }}
                        </p>
                        <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-4">
                            @foreach (['Fichas habilitadas' => $fichas->count(), 'Cargos ofrecidos' => $convocatoria->convocatoriaCargos->count(), 'Cupos' => $convocatoria->convocatoriaCargos->sum('cupos'), 'Postulados' => $postulados] as $texto => $valor)
                                <div class="rounded-2xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">{{ $texto }}</dt><dd class="text-lg font-extrabold text-[#064C3D]">{{ $valor }}</dd></div>
                            @endforeach
                        </dl>

                        @if ($fichas->isNotEmpty())
                            <h3 class="mt-5 text-sm font-semibold text-slate-700">Fichas habilitadas</h3>
                            <div class="mt-2 grid gap-2 sm:grid-cols-3">
                                @foreach ($fichas as $ficha)
                                    <div class="rounded-2xl p-3 text-sm ring-1 ring-slate-200"><p class="font-semibold text-slate-800">Ficha {{ $ficha->code }}</p><p class="text-xs text-slate-500">{{ $ficha->programa }} · {{ $ficha->aprendices }} aprendices</p></div>
                                @endforeach
                            </div>
                        @endif

                        @if ($excepciones->isNotEmpty())
                            <h3 class="mt-5 text-sm font-semibold text-slate-700">Programas habilitados por excepción <span class="font-normal text-slate-400">(RN-048)</span></h3>
                            <div class="mt-2 space-y-2">
                                @foreach ($excepciones as $cc)
                                    <div class="rounded-2xl bg-amber-50 p-3 text-sm text-amber-900">
                                        <p><strong>{{ $cc->cargoActivado->cargo->nombre }}</strong> abierto a {{ collect($cc->programas_extra)->map(fn ($id) => $programas[$id] ?? "programa #$id")->implode(', ') }}.</p>
                                        <p class="mt-1 text-xs">Justificación del Gestor: «{{ $cc->justificacion_excepcion }}».</p>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <details class="group mt-5">
                            <summary class="cursor-pointer text-sm font-semibold text-emerald-700 hover:underline">Ver los {{ $convocatoria->convocatoriaCargos->count() }} cargos con sus requisitos</summary>
                            <div class="mt-3 overflow-x-auto">
                                <table class="w-full text-left text-sm">
                                    <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th class="px-3 py-2 font-semibold">Cargo</th><th class="px-3 py-2 text-center font-semibold">Cupos</th><th class="px-3 py-2 font-semibold">Requisitos</th><th class="px-3 py-2 font-semibold">Documentos</th></tr></thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($convocatoria->convocatoriaCargos->sortBy(fn ($cc) => $cc->cargoActivado->cargo->nombre) as $cc)
                                            <tr class="align-top">
                                                <td class="px-3 py-2 font-semibold text-slate-800">{{ $cc->cargoActivado->cargo->nombre }}</td>
                                                <td class="px-3 py-2 text-center">{{ $cc->cupos }}</td>
                                                <td class="px-3 py-2 text-slate-600">{{ $cc->requisitos ?: '—' }}</td>
                                                <td class="px-3 py-2 text-xs text-slate-500">{{ collect($cc->documentos)->pluck('descripcion')->implode(' · ') ?: '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </details>
                    </section>
                @endif
            </div>

            @if ($convocatoria)
                <aside class="space-y-4">
                    <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                        <h2 class="font-bold text-[#064C3D]">Avance de la selección</h2>
                        <div class="mt-4 space-y-4 text-sm">
                            @foreach (array_filter($avance, 'is_int', ARRAY_FILTER_USE_KEY) as [$texto, $hechos, $total])
                                @php($porcentaje = $total ? round($hechos / $total * 100) : 0)
                                <div>
                                    <div class="flex justify-between"><span class="text-slate-600">{{ $texto }}</span><span class="font-bold text-[#064C3D]">{{ $hechos }} / {{ $total }}</span></div>
                                    <div class="mt-1.5 h-2 rounded-full bg-slate-100" role="progressbar" aria-label="{{ $texto }}" aria-valuenow="{{ $porcentaje }}" aria-valuemin="0" aria-valuemax="100">
                                        <div class="h-2 rounded-full {{ $porcentaje >= 100 ? 'bg-[#064C3D]' : ($porcentaje >= 50 ? 'bg-emerald-500' : 'bg-amber-400') }}" style="width: {{ $porcentaje }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                            <div class="flex justify-between"><span class="text-slate-600">Aprendices por reubicar</span><span class="font-bold {{ $avance['por_reubicar'] ? 'text-amber-600' : 'text-[#064C3D]' }}">{{ $avance['por_reubicar'] }}</span></div>
                        </div>
                    </section>
                    <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                        <h2 class="font-bold text-[#064C3D]">Evaluadores de la convocatoria</h2>
                        <ul class="mt-3 space-y-2 text-sm">
                            @forelse ($evaluadores as $evaluador)
                                <li class="flex items-center justify-between gap-2"><span class="text-slate-700">{{ $evaluador->nombre }}</span><span class="text-xs text-slate-500">{{ $evaluador->funcion }}</span></li>
                            @empty
                                <li class="text-slate-500">Aún no hay evaluadores asignados a las entrevistas.</li>
                            @endforelse
                        </ul>
                    </section>
                    <p class="px-2 text-xs text-slate-500"><i class="fas fa-shield-halved mr-1" aria-hidden="true"></i>Desde la supervisión no se ven puntajes, resultados psicotécnicos ni calificaciones individuales.</p>
                </aside>
            @endif
        </div>
    @endif
</x-talentohumano::layouts.lider>
