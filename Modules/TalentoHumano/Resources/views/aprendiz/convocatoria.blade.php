@php [$apertura, $cierre] = $fechas; @endphp

<x-talentohumano::layouts.aprendiz title="Convocatoria" activo="convocatoria" :p="$p">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">Convocatoria y cargos disponibles</h1>
            <p class="mt-1 text-sm text-slate-600">Solo ves los cargos compatibles con tu programa de formación.</p>
        </div>
        @if ($p->fichaHabilitada && $p->convocatoria && $estadoPostulacion === 'abierta' && !$p->postulacion)
            <a href="{{ route('talentohumano.aprendiz.postularme') }}" class="inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-5 py-2.5 text-sm font-bold text-white shadow-md hover:bg-[#043A2F]"><i class="fas fa-file-signature" aria-hidden="true"></i> Postularme</a>
        @endif
    </div>

    @if (!$p->fichaHabilitada)
        @include('talentohumano::aprendiz._aviso', ['titulo' => 'Tu ficha no participa en la convocatoria actual', 'texto' => 'La convocatoria y sus cargos solo están disponibles para los aprendices de las fichas habilitadas.'])
    @elseif (!$p->convocatoria)
        @include('talentohumano::aprendiz._aviso', ['icono' => 'fa-bullhorn', 'titulo' => 'La convocatoria aún no ha sido publicada', 'texto' => 'Cuando el Gestor de Talento Humano la publique, verás aquí los cargos a los que puedes postularte.'])
    @else
        <div class="mb-5 flex flex-wrap items-center gap-3 rounded-2xl px-4 py-3 text-sm {{ $estadoPostulacion === 'abierta' ? 'bg-emerald-50 text-emerald-900' : 'bg-amber-50 text-amber-900' }}" role="status">
            <i class="fas {{ $estadoPostulacion === 'abierta' ? 'fa-door-open' : 'fa-lock' }}" aria-hidden="true"></i>
            <span>
                @if ($p->postulacion)
                    Ya estás postulado en esta convocatoria. <a href="{{ route('talentohumano.aprendiz.postulacion') }}" class="font-semibold underline">Ver mi postulación</a>
                @elseif ($estadoPostulacion === 'por_abrir')
                    <strong>La postulación abre el {{ $apertura?->format('d/m/Y') }}.</strong> Mientras tanto puedes consultar los cargos.
                @elseif ($estadoPostulacion === 'abierta')
                    <strong>Postulación abierta</strong>{{ $cierre ? ' hasta el ' . $cierre->format('d/m/Y') : '' }}. Debes elegir 3 cargos en orden de preferencia.
                @else
                    <strong>La convocatoria está cerrada.</strong>
                @endif
            </span>
            @if ($apertura)
                <span class="ml-auto text-xs font-semibold">Apertura {{ $apertura->format('d/m/Y') }} · Cierre {{ $cierre?->format('d/m/Y') }}</span>
            @endif
        </div>

        @if ($cargos->isEmpty())
            @include('talentohumano::aprendiz._aviso', ['icono' => 'fa-briefcase', 'titulo' => 'No hay cargos compatibles con tu programa', 'texto' => 'Ningún cargo de esta convocatoria admite tu programa de formación.'])
        @else
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($cargos as $cargo)
                    <article class="flex flex-col rounded-3xl bg-white p-6 shadow-sm ring-1 {{ $cargo->alterno ? 'ring-amber-200' : 'ring-slate-200/70' }}">
                        <div class="flex flex-wrap items-center gap-2 text-xs">
                            <span class="rounded-full bg-[#E8F3EF] px-2.5 py-0.5 font-bold text-[#064C3D]">{{ \Modules\TalentoHumano\Entities\Cargo::NIVELES[$cargo->nivel] ?? $cargo->nivel }}</span>
                            <span class="rounded-full bg-slate-100 px-2.5 py-0.5 font-semibold text-slate-600">{{ $cargo->area ?? 'Sin área' }}</span>
                            <span class="ml-auto font-semibold text-slate-500">{{ $cargo->cupos }} {{ $cargo->cupos == 1 ? 'cupo' : 'cupos' }}</span>
                        </div>
                        <h2 class="mt-3 text-lg font-bold text-slate-900">{{ $cargo->nombre }}</h2>
                        @if ($cargo->rol_principal)
                            <p class="mt-1 text-sm text-slate-600">{{ $cargo->rol_principal }}</p>
                        @endif
                        @if ($cargo->alterno)
                            <p class="mt-3 rounded-xl bg-amber-50 px-3 py-2 text-xs text-amber-900"><i class="fas fa-triangle-exclamation mr-1" aria-hidden="true"></i>Tu programa es alterno para este cargo: al postularte deberás confirmarlo.</p>
                        @endif
                        <div class="mt-4 space-y-3 border-t border-slate-100 pt-4 text-sm">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Requisitos y competencias</p>
                                <p class="mt-1 whitespace-pre-line text-slate-700">{{ $cargo->requisitos ?: 'Sin requisitos adicionales.' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Documentos obligatorios</p>
                                <ul class="mt-1 space-y-0.5 text-slate-700">
                                    @foreach ($cargo->documentos as $doc)
                                        <li><i class="fas fa-file-lines mr-1.5 text-emerald-600" aria-hidden="true"></i>{{ $doc['descripcion'] ?? $doc['tipo'] }}</li>
                                    @endforeach
                                    <li><i class="fas fa-file-pen mr-1.5 text-emerald-600" aria-hidden="true"></i>Propuesta de trabajo para este cargo</li>
                                </ul>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    @endif
</x-talentohumano::layouts.aprendiz>
