<x-talentohumano::layouts.master title="Validar selección · {{ $cargo->nombre }}">
    {{-- Breadcrumbs y Volver --}}
    <div class="mb-6">
        <a href="{{ route('talentohumano.seleccion.index') }}"
           class="inline-flex items-center gap-2 text-xs font-bold text-emerald-700 hover:text-[#064C3D] hover:underline mb-3">
            <i class="fas fa-arrow-left"></i> Aprobaciones pendientes
        </a>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">
                    Propuesta de selección · {{ $convocatoria->fase->romano ?? 'Fase XXXVI' }}
                </p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">
                    {{ $cargo->nombre }}
                </h1>
            </div>
            <div class="flex flex-wrap gap-2 text-xs">
                <span class="rounded-full bg-white px-3 py-1 font-semibold text-slate-600 ring-1 ring-slate-200 shadow-sm">
                    {{ $convocatoriaCargo->cupos }} {{ $convocatoriaCargo->cupos == 1 ? 'cupo' : 'cupos' }}
                </span>
                <span class="rounded-full bg-white px-3 py-1 font-semibold text-slate-600 ring-1 ring-slate-200 shadow-sm">
                    Umbral 120 de 200
                </span>
                <span class="rounded-full bg-white px-3 py-1 font-semibold text-slate-600 ring-1 ring-slate-200 shadow-sm">
                    {{ $esVacante ? 'Si nadie alcanza: declarar vacante' : 'Si nadie alcanza: mayor puntaje' }}
                </span>
            </div>
        </div>
    </div>

    {{-- Avisos del estado del cargo --}}
    <div id="contenedor-aviso" class="mb-4 space-y-3">
        @if ($esEmpate)
            <div class="flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 shadow-sm" role="alert">
                <i class="fas fa-scale-unbalanced mt-0.5 text-base text-red-600"></i>
                <div>
                    <strong class="font-bold">Empate por resolver:</strong> dos candidatos de 1.ª opción con 147 puntos. Elige uno y escribe la justificación.
                </div>
            </div>
        @elseif ($esVacante)
            <div class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 shadow-sm" role="alert">
                <i class="fas fa-ban mt-0.5 text-base text-slate-500"></i>
                <div>
                    Ningún candidato alcanza el umbral de 120. Según la política de la convocatoria, el sistema propone <strong>declarar el cargo vacante</strong>.
                </div>
            </div>
        @endif

        {{-- Alerta dinámica cuando se ajustan candidatos --}}
        <div id="alerta-ajuste" class="hidden flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 shadow-sm" role="alert">
            <i class="fas fa-circle-exclamation mt-0.5 text-base text-amber-600"></i>
            <div>
                <strong>Ajuste a la propuesta:</strong> Has modificado los candidatos preseleccionados por mérito.
                <strong>Todo ajuste a la selección requiere justificación obligatoria (RN-021).</strong>
            </div>
        </div>
    </div>

    {{-- Bloque 1: Tabla de candidatos por jerarquía de mérito --}}
    @include('talentohumano::seleccion.partials.candidatos_tabla')

    {{-- Bloque 2: Formulario de decisión o estado de validación --}}
    @include('talentohumano::seleccion.partials.decision_panel')

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const checkboxes = document.querySelectorAll('.candidato-chk');
                const alertaAjuste = document.getElementById('alerta-ajuste');
                const labelObligatoria = document.getElementById('label-obligatoria');
                const inputEsAjuste = document.getElementById('input-es-ajuste');
                const textareaJust = document.getElementById('justificacion');

                const esEmpate = {{ $esEmpate ? 'true' : 'false' }};

                function verificarCambios() {
                    let huboCambio = false;
                    checkboxes.forEach(chk => {
                        const original = chk.getAttribute('data-original') === '1';
                        if (chk.checked !== original) {
                            huboCambio = true;
                        }
                    });

                    if (huboCambio || esEmpate) {
                        alertaAjuste && alertaAjuste.classList.remove('hidden');
                        if (labelObligatoria) {
                            labelObligatoria.textContent = '(obligatoria)';
                            labelObligatoria.className = 'font-bold text-red-600';
                        }
                        if (inputEsAjuste) inputEsAjuste.value = '1';
                        if (textareaJust) textareaJust.setAttribute('required', 'required');
                    } else {
                        alertaAjuste && alertaAjuste.classList.add('hidden');
                        if (labelObligatoria) {
                            labelObligatoria.textContent = '(obligatoria si ajustas)';
                            labelObligatoria.className = 'font-normal text-slate-400';
                        }
                        if (inputEsAjuste) inputEsAjuste.value = '0';
                        if (textareaJust) textareaJust.removeAttribute('required');
                    }
                }

                checkboxes.forEach(chk => {
                    chk.addEventListener('change', verificarCambios);
                });
            });
        </script>
    @endpush
</x-talentohumano::layouts.master>
