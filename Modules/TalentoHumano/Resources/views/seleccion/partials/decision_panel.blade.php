{{-- Bloque: Panel de decisión y validación institucional (HU-038 · RN-021) --}}
@if (!$esValidado)
    <form id="form-decision"
          action="{{ route('talentohumano.seleccion.validar.guardar', $convocatoriaCargo->id) }}"
          method="POST"
          class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70 mb-6">
        @csrf
        <input type="hidden" name="es_ajuste" id="input-es-ajuste" value="0">

        <h2 class="font-bold text-[#064C3D] text-lg">Tu decisión</h2>
        <p class="mt-1 text-sm text-slate-600">
            Valida la propuesta tal como está o cambia los candidatos marcados arriba. Todo ajuste exige justificación.
        </p>

        <div class="mt-4">
            <label for="justificacion" class="mb-1 block text-sm font-semibold text-slate-700">
                Justificación <span id="label-obligatoria" class="font-normal text-slate-400">{{ $esEmpate ? '(obligatoria)' : '(obligatoria si ajustas)' }}</span>
            </label>
            <textarea id="justificacion"
                      name="justificacion"
                      rows="3"
                      placeholder="Escribe aquí los motivos o acuerdos del comité..."
                      class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm focus:border-[#064C3D] focus:outline-none focus:ring-2 focus:ring-[#34D399]/40 @error('justificacion') border-red-500 @enderror">{{ old('justificacion') }}</textarea>
            @error('justificacion')
                <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 pt-2">
            <p class="text-xs text-slate-500">
                <i class="fas fa-clock-rotate-left mr-1"></i> Se registra con tu designación de Instructor de Talento Humano y queda en la auditoría del sistema.
            </p>
            <button type="submit"
                    class="rounded-full bg-[#064C3D] px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#064C3D]/20 hover:bg-[#043A2F] transition flex items-center gap-2">
                <i class="fas fa-check"></i> Validar
            </button>
        </div>
    </form>
@else
    {{-- Cargo Ya Validado (Bloqueo de seguridad INS-03) --}}
    <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70 mb-6 border-l-4 border-emerald-500">
        <h2 class="font-bold text-[#064C3D] text-lg flex items-center gap-2">
            <i class="fas fa-circle-check text-emerald-600"></i>
            <span>Validación en firme</span>
        </h2>
        <p class="mt-2 text-sm text-slate-700 leading-relaxed">
            Validada por <strong>{{ $primeraSeleccion->validadaPor->full_name ?? 'Instructor de TH' }}</strong> con la designación
            <span class="font-semibold text-emerald-800">{{ $primeraSeleccion->validada_con === 'instructor_th' ? 'Instructor de Talento Humano' : 'Líder de la estrategia' }}</span>
            el {{ optional($primeraSeleccion->fecha_validacion)->format('d/m/Y \a \l\a\s g:i a') ?? '21/10/2026' }}.
            @if ($primeraSeleccion->justificacion)
                <br><span class="text-xs text-slate-500 font-medium">Motivo / Justificación: «{{ $primeraSeleccion->justificacion }}»</span>
            @endif
        </p>
        <p class="mt-3 text-xs text-slate-400 flex items-center gap-1.5">
            <i class="fas fa-lock"></i> Un cargo se valida una sola vez: ya no se puede volver a validar ni ajustar.
        </p>
    </section>
@endif
