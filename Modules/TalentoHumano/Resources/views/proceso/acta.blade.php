<x-talentohumano::layouts.master title="Acta de seleccionados · Fase {{ $fase->numero }}">
    <x-talentohumano::encabezado titulo="Acta de seleccionados · Fase {{ $fase->numero }}" :volver="route('talentohumano.fases.show', $fase->id)" hu="HU-028A · HU-028B · RF-012 · RN-023"
        subtitulo="El sistema genera el acta con las selecciones validadas, las reubicaciones del comité y los cargos vacantes. Al publicarla se envía a todos los postulantes y se crean las asignaciones de cargo." />

    @if (!$convocatoria)
        <div class="rounded-2xl bg-white p-10 text-center text-slate-600 shadow-sm ring-1 ring-slate-200">
            <i class="fas fa-lock mr-1 text-amber-600" aria-hidden="true"></i> Primero publique la convocatoria de la fase.
        </div>
    @else
        @if ($publicada)
            <div class="mb-6 rounded-2xl bg-[#E8F3EF] p-4 text-sm text-[#064C3D]">
                <i class="fas fa-circle-check mr-1" aria-hidden="true"></i>
                Acta publicada el {{ $publicada->fecha_publicacion->format('d/m/Y \a \l\a\s g:i a') }}. Ya está disponible para los postulantes y se habilitó el empalme.
            </div>
        @elseif ($pendientes)
            <div class="mb-6 rounded-2xl bg-amber-50 p-4 text-sm text-amber-900 ring-1 ring-amber-200" role="alert">
                <p class="font-semibold"><i class="fas fa-triangle-exclamation mr-1" aria-hidden="true"></i> Todavía no se puede publicar el acta. Falta:</p>
                <ul class="mt-2 list-disc space-y-0.5 pl-5">
                    @foreach ($pendientes as $pendiente)
                        <li>{{ $pendiente }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- HU-037D: lo que necesita la reunión del comité de reubicación, sin puntajes --}}
        @if ($porReubicar->isNotEmpty())
            <div class="mb-6 grid gap-4 lg:grid-cols-2">
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <h2 class="font-bold text-[#064C3D]">Aprendices por reubicar ({{ $porReubicar->count() }})</h2>
                    <p class="text-xs text-slate-500">No quedaron seleccionados en ninguna de sus 3 opciones. Los decide el comité de reubicación.</p>
                    <ul class="mt-3 space-y-1 text-sm">
                        @foreach ($porReubicar as $postulacion)
                            <li>{{ $postulacion->nombre_completo }} <span class="text-xs text-slate-500">· ficha {{ $postulacion->course?->code }} · {{ $postulacion->course?->program?->name }}</span></li>
                        @endforeach
                    </ul>
                </div>
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <h2 class="font-bold text-[#064C3D]">Cargos con cupos vacantes ({{ $cuposVacantes->count() }})</h2>
                    <ul class="mt-3 space-y-1 text-sm">
                        @forelse ($cuposVacantes as $vacante)
                            <li>{{ $vacante['cargo_activado']->cargo->nombre }} <span class="text-xs text-slate-500">· {{ $vacante['vacantes'] }} {{ $vacante['vacantes'] == 1 ? 'cupo' : 'cupos' }}</span></li>
                        @empty
                            <li class="text-slate-500">No quedan cupos vacantes.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        @endif

        {{-- El acta, generada con la plantilla del sistema (no se carga un archivo externo, escenario 2) --}}
        <article class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
            <header class="border-b border-slate-200 pb-4 text-center">
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">SENA · Centro de Formación Agroindustrial La Angostura · SENA Empresa</p>
                <h2 class="mt-2 text-xl font-extrabold text-[#064C3D]">Acta de seleccionados · Fase {{ $fase->numero }}</h2>
                <p class="text-sm text-slate-600">{{ $convocatoria->titulo }}</p>
            </header>

            <table class="mt-5 min-w-full text-left text-sm">
                <thead class="text-xs uppercase tracking-wide text-slate-500">
                    <tr class="border-b border-slate-200"><th class="py-2 pr-4">Cargo</th><th class="py-2 pr-4">Aprendiz</th><th class="py-2 pr-4">Ficha</th><th class="py-2">Observación</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($filas as $fila)
                        <tr>
                            <td class="py-2.5 pr-4 font-semibold text-slate-900">{{ $fila->cargo }}</td>
                            <td class="py-2.5 pr-4">{{ $fila->aprendiz }}</td>
                            <td class="py-2.5 pr-4">{{ $fila->ficha }}</td>
                            <td class="py-2.5">
                                @if ($fila->observacion)
                                    <span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700">{{ $fila->observacion }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-6 text-center text-slate-500">Aún no hay selecciones validadas.</td></tr>
                    @endforelse
                    @foreach ($vacantes as $cargo)
                        <tr>
                            <td class="py-2.5 pr-4 font-semibold text-slate-900">{{ $cargo }}</td>
                            <td class="py-2.5 pr-4 text-slate-400" colspan="2">—</td>
                            <td class="py-2.5"><span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">Vacante</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </article>

        @if (!$publicada && !$soloConsulta)
            <form method="POST" action="{{ route('talentohumano.fases.acta.publicar', $fase->id) }}" class="mt-6 flex justify-end"
                  onsubmit="return confirm('Al publicar el acta se envía por correo a todos los postulantes, queda disponible para consulta y se crean las asignaciones de cargo. Ya no se podrá modificar. ¿Confirmar publicación?')">
                @csrf
                <button type="submit" @disabled($pendientes)
                        class="inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-6 py-2.5 text-sm font-semibold text-white hover:bg-[#043A2F] disabled:cursor-not-allowed disabled:opacity-50">
                    <i class="fas fa-bullhorn" aria-hidden="true"></i> Publicar acta
                </button>
            </form>
        @endif
    @endif
</x-talentohumano::layouts.master>
