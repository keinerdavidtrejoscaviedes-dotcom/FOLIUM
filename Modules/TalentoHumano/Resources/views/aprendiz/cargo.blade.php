<x-talentohumano::layouts.aprendiz title="Mi Cargo e Historial" activo="cargo" :estado="$estado" :estados="$estados">
    <div class="mx-auto max-w-3xl">
        
        {{-- Encabezado --}}
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">Mi Cargo e Historial</h1>
                <p class="mt-0.5 text-xs text-slate-500">Asignaciones en SENA Empresa · Centro de Formación La Angostura.</p>
            </div>
            <span class="rounded-full bg-[#E8F3EF] px-3 py-1 text-xs font-bold text-[#064C3D]">
                HU-055 · RF-015
            </span>
        </div>

        {{-- Tarjeta de Asignación en la Fase Actual --}}
        <section class="entrada rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 pb-4">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        Fase XXXVI · Origen: Acta de seleccionados
                    </span>
                    <h2 class="mt-1 text-xl font-extrabold text-[#064C3D] sm:text-2xl">
                        Líder de Unidad de Formación Productiva Cacao
                    </h2>
                    <p class="mt-1 text-xs text-slate-500">
                        Área Agrícola · Jefe inmediato: <strong>Gestor Agrícola</strong> · Unidad Productiva: <strong>Cacao</strong>
                    </p>
                </div>

                <div>
                    @if ($estado === 'asignada')
                        <span class="rounded-full bg-sky-100 px-3.5 py-1 text-xs font-bold text-sky-800">
                            Asignada (Empalme en curso)
                        </span>
                    @elseif ($estado === 'vigente')
                        <span class="rounded-full bg-emerald-100 px-3.5 py-1 text-xs font-bold text-emerald-800 flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span> Vigente (Vinculado)
                        </span>
                    @else
                        <span class="rounded-full bg-slate-100 px-3.5 py-1 text-xs font-bold text-slate-600">
                            Fase cerrada (Histórico)
                        </span>
                    @endif
                </div>
            </div>

            {{-- Mensaje de Estado (HU-055) --}}
            @if ($estado === 'asignada')
                <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs font-medium text-amber-950">
                    <div class="flex items-start gap-2.5">
                        <i class="fas fa-lock text-amber-600 text-sm mt-0.5"></i>
                        <span>Debe completar el empalme y recibir el visto bueno para habilitar las funciones del cargo (RN-028).</span>
                    </div>
                    <a href="{{ route('talentohumano.aprendiz.empalme', ['estado' => 'entrante']) }}" class="rounded-full bg-amber-500 px-4 py-1.5 text-xs font-bold text-white hover:bg-amber-600 transition shadow-xs">
                        Ir a mi empalme <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            @elseif ($estado === 'vigente')
                <div class="mt-4 flex items-start gap-2.5 rounded-2xl bg-[#E8F3EF] p-4 text-xs font-medium text-[#064C3D]">
                    <i class="fas fa-circle-check text-emerald-600 text-sm mt-0.5"></i>
                    <span>
                        ¡Asignación en vigencia! Quedaste plenamente vinculado con el visto bueno del Gestor de Talento Humano. Los demás módulos de SENA Empresa ya te reconocen como responsable del cargo.
                    </span>
                </div>
            @else
                <div class="mt-4 flex items-start gap-2.5 rounded-2xl bg-slate-100 p-4 text-xs text-slate-700">
                    <i class="fas fa-flag-checkered text-slate-500 text-sm mt-0.5"></i>
                    <span>
                        La Fase XXXVI ha finalizado. Las funciones de cargo fueron cerradas, pero conservas tu historial formativo para certificar tu etapa en SENA Empresa.
                    </span>
                </div>
            @endif

            {{-- Resumen de Funciones del Cargo --}}
            <div class="mt-5 border-t border-slate-100 pt-4">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Responsabilidades clave</p>
                <ul class="space-y-1.5 text-xs text-slate-600">
                    <li class="flex items-center gap-2">
                        <i class="fas fa-check text-[#34D399]"></i> Dirigir las labores agronómicas diarias de la unidad de cacao.
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="fas fa-check text-[#34D399]"></i> Custodiar el inventario de herramientas asignadas a la unidad.
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="fas fa-check text-[#34D399]"></i> Rendir informes periódicos y diligenciar evidencias en el módulo correspondiente.
                    </li>
                </ul>
            </div>
        </section>

        {{-- Línea de Tiempo de Historial Formativo --}}
        <section class="mt-6 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
            <h3 class="font-bold text-[#064C3D] flex items-center gap-2 border-b border-slate-100 pb-3">
                <i class="fas fa-clock-rotate-left text-emerald-600"></i> Mi historial en SENA Empresa
            </h3>

            <ol class="relative mt-5 space-y-4 border-l-2 border-[#E8F3EF] pl-6 text-xs sm:text-sm">
                @foreach ($historial as $h)
                    <li class="relative">
                        <span @class([
                            'absolute -left-[31px] top-1 h-3.5 w-3.5 rounded-full ring-4 ring-white',
                            'bg-[#064C3D]' => $h['estado'] === 'Vigente' || $h['estado'] === 'Asignada',
                            'bg-slate-300' => $h['estado'] === 'Finalizada',
                        ])></span>
                        
                        <div class="flex flex-wrap items-center justify-between gap-1">
                            <h4 class="font-bold text-slate-800">{{ $h['cargo'] }} · {{ $h['fase'] }}</h4>
                            <span @class([
                                'rounded-full px-2 py-0.5 text-[10px] font-bold',
                                'bg-emerald-100 text-emerald-800' => $h['estado'] === 'Vigente',
                                'bg-sky-100 text-sky-800' => $h['estado'] === 'Asignada',
                                'bg-slate-100 text-slate-600' => $h['estado'] === 'Finalizada',
                            ])>
                                {{ $h['estado'] }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5">{{ $h['fechas'] }}</p>
                        <p class="text-xs text-slate-600 mt-1 leading-relaxed">{{ $h['detalle'] }}</p>
                    </li>
                @endforeach
            </ol>
        </section>

    </div>
</x-talentohumano::layouts.aprendiz>
