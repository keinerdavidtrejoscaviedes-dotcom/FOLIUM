@php
    $persona = $p->persona;
    $accesos = array_values(array_filter([
        $p->fichaHabilitada ? ['talentohumano.aprendiz.cronograma', 'fa-calendar-days', 'Cronograma', 'Fechas aprobadas de cada etapa del proceso de selección.', $p->cronograma ? 'Publicado' : 'Sin publicar'] : null,
        $p->fichaHabilitada ? ['talentohumano.aprendiz.convocatoria', 'fa-bullhorn', 'Convocatoria', 'Cargos compatibles con tu programa, requisitos y documentos.', $p->convocatoria ? 'Publicada' : 'Sin publicar'] : null,
        ['talentohumano.aprendiz.postulacion', 'fa-id-card', 'Mi postulación', 'Tus 3 opciones, tu prueba, tus entrevistas y tu resultado.', $p->postulacion ? 'Registrada' : 'Sin postulación'],
        ['talentohumano.aprendiz.prueba', 'fa-brain', 'Prueba psicotécnica', 'Prerrequisito para la entrevista. Se guarda mientras respondes.', match ($aplicacion?->estado) { 'presentada' => 'Presentada', 'no_evaluado' => 'No presentada', default => $p->postulacion ? 'Pendiente' : 'Solo postulados' }],
        ['talentohumano.aprendiz.acta', 'fa-user-check', 'Acta de seleccionados', 'Resultado oficial de la selección de tu convocatoria.', null],
        ['talentohumano.aprendiz.empalme', 'fa-handshake', 'Mi empalme', 'Inventario, visita a la unidad productiva y firma del acta.', $p->empalme ? ucfirst(str_replace('_', ' ', $p->empalme->estado)) : null],
        ['talentohumano.aprendiz.cargo', 'fa-id-card-clip', 'Mi cargo e historial', 'Tu cargo asignado o vigente y los de fases anteriores.', $p->asignacion ? ucfirst($p->asignacion->estado) : null],
    ]));
@endphp

<x-talentohumano::layouts.aprendiz title="Inicio" activo="panel" :p="$p">
    <section class="relative isolate mb-6 overflow-hidden rounded-3xl bg-[#064C3D] p-6 text-white shadow-xl shadow-[#064C3D]/10 sm:p-8">
        <div class="folium-dots absolute inset-0 -z-10" aria-hidden="true"></div>
        <i class="fas fa-leaf absolute -bottom-6 -right-6 -z-10 rotate-12 text-[10rem] text-white/5" aria-hidden="true"></i>
        <p class="entrada text-xs font-bold uppercase tracking-[.2em] text-[#34D399]">FOLIUM · Talento Humano</p>
        <h1 class="entrada mt-2 text-2xl font-extrabold tracking-tight sm:text-3xl">¡Hola, {{ $persona->first_name ?? 'aprendiz' }}!</h1>
        <p class="entrada mt-1.5 max-w-2xl text-sm text-emerald-50/85">Aquí consultas la convocatoria de SENA Empresa, te postulas, presentas la prueba psicotécnica y haces el empalme de tu cargo.</p>
        <div class="entrada mt-4 flex flex-wrap gap-2 text-xs">
            @if ($persona?->ficha)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 font-semibold ring-1 ring-white/20"><i class="fas fa-users-rectangle text-[#34D399]" aria-hidden="true"></i> Ficha {{ $persona->ficha }} · {{ $persona->programa }}</span>
            @endif
            @if ($p->fase)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-[#34D399] px-3 py-1 font-bold text-[#043A2F]"><i class="fas fa-flag" aria-hidden="true"></i> {{ $p->fase->nombre_completo }}</span>
            @endif
        </div>
    </section>

    @if ($paso)
        @php [$titulo, $texto, $ruta, $icono] = $paso; @endphp
        <div class="entrada mb-6 flex flex-wrap items-center justify-between gap-4 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
            <div class="flex items-start gap-4">
                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-amber-100 text-lg text-amber-700"><i class="fas {{ $icono }}" aria-hidden="true"></i></span>
                <div>
                    <p class="text-xs font-bold uppercase tracking-[.18em] text-emerald-600">Tu siguiente paso</p>
                    <h2 class="mt-1 text-xl font-extrabold text-[#064C3D]">{{ $titulo }}</h2>
                    <p class="mt-0.5 text-sm text-slate-600">{{ $texto }}</p>
                </div>
            </div>
            <a href="{{ route($ruta) }}" class="inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-6 py-3 text-sm font-bold text-white shadow-lg shadow-[#064C3D]/20 hover:bg-[#043A2F]">Continuar <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        </div>
    @elseif (!$p->fichaHabilitada && !$p->asignacion && !$p->empalme)
        <div class="mb-6">
            @include('talentohumano::aprendiz._aviso', [
                'titulo' => 'Tu ficha no participa en la convocatoria actual',
                'texto' => 'Cuando tu ficha sea habilitada para la fase, aquí verás el cronograma, los cargos compatibles y el acceso a la postulación.',
            ])
        </div>
    @endif

    @if ($p->asignacion?->estado === 'asignada')
        <p class="mb-6 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" role="status">
            <i class="fas fa-circle-info mt-0.5 text-amber-500" aria-hidden="true"></i>
            <span>Tu asignación como <strong>{{ $p->asignacion->cargo?->nombre }}</strong> está en estado «asignada»: debe completar el empalme para usar las funciones del cargo.</span>
        </p>
    @endif

    <h2 class="mb-4 text-xs font-bold uppercase tracking-[.18em] text-slate-400">Tus secciones</h2>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($accesos as [$ruta, $icono, $titulo, $texto, $etiqueta])
            <a href="{{ route($ruta) }}" class="group flex flex-col justify-between rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70 transition hover:shadow-xl hover:shadow-[#064C3D]/10 hover:ring-[#34D399]">
                <div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="grid h-12 w-12 place-items-center rounded-2xl bg-[#E8F3EF] text-lg text-[#064C3D] transition group-hover:bg-[#064C3D] group-hover:text-[#34D399]"><i class="fas {{ $icono }}" aria-hidden="true"></i></span>
                        @if ($etiqueta)
                            <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">{{ $etiqueta }}</span>
                        @endif
                    </div>
                    <h3 class="mt-5 text-base font-bold text-slate-900 group-hover:text-[#064C3D]">{{ $titulo }}</h3>
                    <p class="mt-1 text-xs leading-relaxed text-slate-500">{{ $texto }}</p>
                </div>
                <span class="mt-4 text-xs font-semibold text-emerald-700">Abrir <i class="fas fa-arrow-right ml-1 transition group-hover:translate-x-1" aria-hidden="true"></i></span>
            </a>
        @endforeach
    </div>
</x-talentohumano::layouts.aprendiz>
