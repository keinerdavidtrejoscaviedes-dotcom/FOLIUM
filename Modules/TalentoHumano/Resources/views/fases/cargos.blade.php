<x-talentohumano::layouts.master title="Activación de cargos · Fase {{ $fase->numero }}">
    <x-talentohumano::encabezado titulo="Activación de cargos · Fase {{ $fase->numero }}" :volver="route('talentohumano.fases.show', $fase->id)" hu="HU-019 · RF-001 · RN-037 · RN-056"
        subtitulo="El sistema propone los cargos del catálogo compatibles con los programas de las fichas de la fase y todos los cargos de nivel Gestor. Ajuste la lista y el número de aprendices de cada cargo." />

    @if ($fichas->isEmpty())
        {{-- HU-019 escenario 11 --}}
        <div class="rounded-2xl bg-white p-10 text-center shadow-sm ring-1 ring-slate-200">
            <p class="text-slate-600">Primero debe seleccionar las fichas de la fase.</p>
            <a href="{{ route('talentohumano.fases.fichas.index', $fase->id) }}" class="mt-4 inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#043A2F]">
                <i class="fas fa-users-rectangle" aria-hidden="true"></i> Ir a Fichas de la fase
            </a>
        </div>
    @else
        @php
            // Al volver de un error se muestra lo que se envió, no lo guardado
            $hayOld = old('cargos') !== null;
        @endphp

        @if ($bloqueada)
            <div class="mb-6 rounded-2xl bg-amber-50 p-4 text-sm text-amber-800 ring-1 ring-amber-200">
                <i class="fas fa-lock mr-1" aria-hidden="true"></i>
                La convocatoria ya fue publicada; no se pueden modificar los cargos activados.
            </div>
        @elseif (!$hayActivacion)
            <div class="mb-6 rounded-2xl bg-[#E8F3EF] p-4 text-sm text-[#064C3D]">
                <i class="fas fa-wand-magic-sparkles mr-1" aria-hidden="true"></i>
                Esta es la propuesta del sistema: aún no se ha confirmado la activación de la fase.
            </div>
        @endif

        {{-- Contexto: fichas y unidades productivas (escenario 1) --}}
        <div class="mb-6 grid gap-4 lg:grid-cols-2">
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-sm font-bold text-[#064C3D]">Fichas de la fase</h2>
                <ul class="mt-3 flex flex-wrap gap-2">
                    @foreach ($fichas as $ficha)
                        <li class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-700">
                            <strong>{{ $ficha->code }}</strong> · {{ $ficha->programa }}{{ $ficha->trimestre ? ' · ' . $ficha->trimestre . '.º trim.' : '' }}
                        </li>
                    @endforeach
                </ul>
            </div>
            <details class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <summary class="cursor-pointer text-sm font-bold text-[#064C3D]">Unidades productivas ({{ $unidades->count() }})</summary>
                <ul class="mt-3 flex flex-wrap gap-2">
                    @foreach ($unidades as $unidad)
                        <li class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-700">{{ $unidad->name }}@if ($unidad->sector) <span class="text-slate-400">· {{ $unidad->sector }}</span>@endif</li>
                    @endforeach
                </ul>
            </details>
        </div>

        <x-talentohumano::errores />

        <form method="POST" action="{{ route('talentohumano.fases.cargos.update', $fase->id) }}" id="form-cargos">
            @csrf
            @method('PUT')

            <div class="space-y-5">
                @foreach ($propuesta as $area => $cargos)
                    <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                        <h2 class="bg-slate-50 px-4 py-3 text-sm font-bold text-[#064C3D]">{{ $area ?: 'Sin área funcional' }}</h2>
                        <div class="divide-y divide-slate-100">
                            @foreach ($cargos as $cargo)
                                @include('talentohumano::fases._cargo', ['esOtro' => false])
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>

            {{-- Escenario 6: agregar manualmente otros cargos del catálogo --}}
            @if ($otros->isNotEmpty())
                <details class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200" @if ($hayOld && $otros->contains(fn ($c) => old("cargos.{$c->id}.activo"))) open @endif>
                    <summary class="cursor-pointer px-4 py-3 text-sm font-bold text-[#064C3D]">
                        <i class="fas fa-plus mr-1" aria-hidden="true"></i> Agregar otros cargos del catálogo ({{ $otros->count() }})
                    </summary>
                    <div class="border-t border-slate-100 px-4 py-3">
                        <p class="text-xs text-slate-500">
                            Estos cargos no son compatibles con los programas de las fichas de la fase. Si agrega alguno, en la convocatoria se podrán habilitar otros programas para ese cargo.
                        </p>
                        <input type="search" id="buscar-cargo" placeholder="Buscar cargo"
                               class="mt-3 w-full rounded-full border-0 px-4 py-2 text-sm ring-1 ring-slate-300 focus:ring-2 focus:ring-[#064C3D] sm:w-80">
                    </div>
                    <div class="divide-y divide-slate-100 border-t border-slate-100" id="otros-cargos">
                        @foreach ($otros as $cargo)
                            @include('talentohumano::fases._cargo', ['esOtro' => true])
                        @endforeach
                    </div>
                </details>
            @endif

            @unless ($bloqueada)
                <div class="sticky bottom-0 mt-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-white/95 p-4 shadow-lg ring-1 ring-slate-200 backdrop-blur">
                    <p class="text-sm text-slate-600">
                        <strong id="total-cargos">0</strong> cargos activados · <strong id="total-aprendices">0</strong> aprendices requeridos
                    </p>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-6 py-2.5 text-sm font-semibold text-white hover:bg-[#043A2F]">
                        <i class="fas fa-check" aria-hidden="true"></i> Confirmar activación
                    </button>
                </div>
            @endunless
        </form>

        @push('scripts')
            <script>
                (() => {
                    const formulario = document.getElementById('form-cargos');
                    const filas = [...formulario.querySelectorAll('[data-cargo]')];

                    const totales = () => {
                        let cargos = 0, aprendices = 0;
                        filas.forEach((fila) => {
                            if (fila.querySelector('[data-activo]').checked) {
                                cargos++;
                                aprendices += Number(fila.querySelector('[data-numero]').value) || 0;
                            }
                        });
                        document.getElementById('total-cargos')?.replaceChildren(cargos);
                        document.getElementById('total-aprendices')?.replaceChildren(aprendices);
                    };

                    formulario.addEventListener('input', totales);
                    formulario.addEventListener('change', totales);
                    totales();

                    document.getElementById('buscar-cargo')?.addEventListener('input', (evento) => {
                        const texto = evento.target.value.trim().toLowerCase();
                        document.querySelectorAll('#otros-cargos [data-cargo]').forEach((fila) => {
                            fila.hidden = texto !== '' && !fila.dataset.nombre.includes(texto);
                        });
                    });

                    // Escenario 5: retirar un cargo de nivel Gestor exige confirmar, mostrando qué empalmes quedarían sin receptor
                    formulario.addEventListener('submit', (evento) => {
                        const gestoresRetirados = filas.filter((fila) => fila.dataset.gestor === '1' && !fila.querySelector('[data-activo]').checked);
                        if (!gestoresRetirados.length) return;

                        const detalle = gestoresRetirados.map((fila) => {
                            const subordinados = JSON.parse(fila.dataset.subordinados);
                            return `• ${fila.dataset.titulo}: ${subordinados.length ? subordinados.join(', ') : 'sin cargos a su cargo'}`;
                        }).join('\n');

                        const mensaje = 'Los cargos de nivel Gestor reciben los empalmes de su área. '
                            + 'Si los retira, quedarían sin receptor los empalmes de:\n\n' + detalle + '\n\n¿Confirmar retiro?';

                        if (!confirm(mensaje)) {
                            evento.preventDefault();
                        }
                    });
                })();
            </script>
        @endpush
    @endif
</x-talentohumano::layouts.master>
