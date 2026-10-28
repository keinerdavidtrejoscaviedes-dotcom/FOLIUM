@use('Modules\TalentoHumano\Http\Controllers\AprendizController')

<x-talentohumano::layouts.aprendiz title="Satisfacción del empalme" activo="satisfaccion" :p="$p">
    <div class="mb-6">
        <h1 class="text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">Formulario de Satisfacción del Empalme</h1>
        <p class="mt-1 text-sm text-slate-600">Lo diligencian el aprendiz que entrega y el que recibe, después de firmar el acta.</p>
    </div>

    @if (!$empalme)
        @include('talentohumano::aprendiz._aviso', ['icono' => 'fa-handshake', 'titulo' => 'No tienes un empalme asignado'])
    @elseif ($respuesta)
        @include('talentohumano::aprendiz._aviso', ['icono' => 'fa-circle-check', 'titulo' => 'Ya diligenciaste el formulario', 'texto' => 'Lo enviaste el ' . \Illuminate\Support\Carbon::parse($respuesta->created_at)->format('d/m/Y h:i a') . '. ' . ($empalme->estado === 'cerrado' ? 'El empalme ya tiene el visto bueno del Gestor de Talento Humano.' : 'Falta el visto bueno del Gestor de Talento Humano para cerrar el empalme.'), 'enlace' => [route('talentohumano.aprendiz.empalme'), 'Ver mi empalme']])
    @elseif ($empalme->acta?->estado !== 'firmada')
        {{-- HU-054, esc. 3 --}}
        @include('talentohumano::aprendiz._aviso', ['icono' => 'fa-lock', 'titulo' => 'Primero debe firmarse el acta de recibimiento', 'texto' => 'El formulario se habilita cuando todas las partes firman el acta del empalme.', 'enlace' => [route('talentohumano.aprendiz.empalme'), 'Ir a mi empalme']])
    @else
        <form method="POST" action="{{ route('talentohumano.aprendiz.satisfaccion.enviar') }}" class="space-y-4">
            @csrf
            <p class="text-sm text-slate-600">Empalme del cargo <strong>{{ $empalme->cargo?->nombre }}</strong>. Todas las preguntas son obligatorias.</p>
            @foreach (AprendizController::PREGUNTAS_SATISFACCION as $i => $pregunta)
                <fieldset class="rounded-3xl bg-white p-5 shadow-sm ring-1 {{ $errors->has("respuestas.$i") ? 'ring-2 ring-red-400' : 'ring-slate-200/70' }}">
                    <legend class="sr-only">Pregunta {{ $i + 1 }}</legend>
                    <p class="font-semibold text-slate-800">{{ $i + 1 }}. {{ $pregunta }}</p>
                    <div class="mt-3 grid gap-2 sm:grid-cols-5">
                        @foreach (AprendizController::ESCALA_SATISFACCION as $valor => $texto)
                            <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 p-2.5 text-xs font-semibold text-slate-600 has-[:checked]:border-[#064C3D] has-[:checked]:bg-[#E8F3EF] has-[:checked]:text-[#064C3D]">
                                <input type="radio" name="respuestas[{{ $i }}]" value="{{ $valor }}" @checked(old("respuestas.$i") == $valor) class="h-4 w-4 accent-[#064C3D]"> {{ $texto }}
                            </label>
                        @endforeach
                    </div>
                    @error("respuestas.$i")<p class="mt-2 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                </fieldset>
            @endforeach
            <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                <label for="comentario" class="font-semibold text-slate-800">Comentarios <span class="font-normal text-slate-400">(opcional)</span></label>
                <textarea id="comentario" name="comentario" rows="3" maxlength="1000" class="mt-2 w-full rounded-2xl border border-slate-200 p-3 text-sm focus:border-[#064C3D] focus:outline-none focus:ring-2 focus:ring-[#34D399]/40">{{ old('comentario') }}</textarea>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-6 py-3 text-sm font-bold text-white shadow-lg shadow-[#064C3D]/20 hover:bg-[#043A2F]"><i class="fas fa-paper-plane" aria-hidden="true"></i> Enviar</button>
            </div>
        </form>
    @endif
</x-talentohumano::layouts.aprendiz>
