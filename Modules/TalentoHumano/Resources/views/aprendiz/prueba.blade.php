<x-talentohumano::layouts.aprendiz title="Prueba psicotécnica" activo="prueba" :p="$p">
    <div class="mb-6">
        <h1 class="text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">Prueba psicotécnica</h1>
        <p class="mt-1 text-sm text-slate-600">Es requisito para que te citen a entrevista. No verás tu puntaje.</p>
    </div>

    @switch($situacion)
        @case('sin_postulacion')
            @include('talentohumano::aprendiz._aviso', ['icono' => 'fa-lock', 'titulo' => 'La prueba está disponible solo para los aprendices postulados', 'texto' => 'Primero debes postularte a la convocatoria.'])
            @break
        @case('presentada')
            @include('talentohumano::aprendiz._aviso', ['icono' => 'fa-circle-check', 'titulo' => 'Ya presentó la prueba psicotécnica', 'texto' => 'Tu resultado quedó registrado. El Gestor de Talento Humano te enviará la citación a tus entrevistas.', 'enlace' => [route('talentohumano.aprendiz.postulacion'), 'Ver mi postulación']])
            @break
        @case('vencida')
            @include('talentohumano::aprendiz._aviso', ['icono' => 'fa-calendar-xmark', 'titulo' => 'El plazo para presentar la prueba venció', 'texto' => 'No presentaste la prueba dentro del plazo del cronograma, por lo que quedaste como «no evaluado» y no se te puede programar entrevista.'])
            @break
        @case('por_abrir')
            @include('talentohumano::aprendiz._aviso', ['icono' => 'fa-hourglass-start', 'titulo' => $etapa?->inicio ? 'La prueba se habilita el ' . $etapa->inicio->format('d/m/Y') : 'La prueba aún no tiene fecha en el cronograma', 'texto' => $etapa?->fin ? 'Podrás presentarla hasta el ' . $etapa->fin->format('d/m/Y') . '.' : null])
            @break
        @case('sin_prueba')
            @include('talentohumano::aprendiz._aviso', ['icono' => 'fa-brain', 'titulo' => 'La prueba aún no está disponible', 'texto' => 'El Evaluador psicológico todavía no ha publicado la prueba. Vuelve más tarde.'])
            @break
        @default
            @php $total = count($preguntas); $respondidas = collect($preguntas)->filter(fn ($pr) => filled($respuestas[$pr['id']] ?? null))->count(); @endphp
            <div class="sticky top-16 z-30 mb-5 rounded-2xl bg-white/95 p-4 shadow-sm ring-1 ring-slate-200/70 backdrop-blur">
                <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                    <p class="font-bold text-[#064C3D]">{{ $titulo }}</p>
                    <p class="text-slate-600"><span id="respondidas">{{ $respondidas }}</span> de {{ $total }} respondidas · plazo hasta el {{ \Illuminate\Support\Carbon::parse($aplicacion->fecha_limite)->format('d/m/Y') }}</p>
                </div>
                <div class="mt-2 h-2 rounded-full bg-slate-100"><div id="barra" class="h-2 rounded-full bg-[#34D399] transition-all" style="width: {{ $total ? round($respondidas * 100 / $total) : 0 }}%"></div></div>
                <p id="guardado" class="mt-1 text-xs text-slate-500" aria-live="polite">Tus respuestas se guardan automáticamente.</p>
            </div>

            <form id="form-prueba" method="POST" action="{{ route('talentohumano.aprendiz.prueba.enviar') }}" class="space-y-4">
                @csrf
                @foreach ($preguntas as $n => $pregunta)
                    @php $valor = $respuestas[$pregunta['id']] ?? null; @endphp
                    <fieldset id="pregunta-{{ $pregunta['id'] }}" data-pregunta="{{ $pregunta['id'] }}" class="scroll-mt-40 rounded-3xl bg-white p-6 shadow-sm ring-1 {{ session('error') && $primeraPendiente === $pregunta['id'] ? 'ring-2 ring-red-400' : 'ring-slate-200/70' }}">
                        <legend class="sr-only">Pregunta {{ $n + 1 }}</legend>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Pregunta {{ $n + 1 }} de {{ $total }}</p>
                        <p class="mt-1 font-semibold text-slate-900">{{ $pregunta['enunciado'] }}</p>
                        @if ($pregunta['imagen'])
                            <img src="{{ route('talentohumano.aprendiz.prueba.imagen', $pregunta['id']) }}" alt="Imagen de la pregunta {{ $n + 1 }}" class="mt-3 max-h-72 rounded-2xl ring-1 ring-slate-200">
                        @endif

                        <div class="mt-4">
                            @if ($pregunta['tipo'] === 'seleccion')
                                <div class="space-y-2">
                                    @foreach ($pregunta['opciones'] as $i => $opcion)
                                        <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 p-3 text-sm has-[:checked]:border-[#064C3D] has-[:checked]:bg-[#E8F3EF]">
                                            <input type="radio" name="respuestas[{{ $pregunta['id'] }}]" value="{{ $i }}" @checked((string) $valor === (string) $i) class="h-4 w-4 accent-[#064C3D]"> {{ $opcion['texto'] }}
                                        </label>
                                    @endforeach
                                </div>
                            @elseif ($pregunta['tipo'] === 'escala')
                                @php $min = $pregunta['opciones']['min'] ?? 1; $max = $pregunta['opciones']['max'] ?? 5; @endphp
                                <div class="flex flex-wrap gap-2">
                                    @for ($v = $min; $v <= $max; $v++)
                                        <label class="grid h-11 min-w-11 cursor-pointer place-items-center rounded-xl border border-slate-200 px-3 text-sm font-semibold has-[:checked]:border-[#064C3D] has-[:checked]:bg-[#064C3D] has-[:checked]:text-white">
                                            <input type="radio" name="respuestas[{{ $pregunta['id'] }}]" value="{{ $v }}" @checked((string) $valor === (string) $v) class="sr-only"> {{ $v }}
                                        </label>
                                    @endfor
                                </div>
                                <p class="mt-1 flex justify-between text-xs text-slate-400"><span>{{ $min }} = Nunca o totalmente en desacuerdo</span><span>{{ $max }} = Siempre o totalmente de acuerdo</span></p>
                            @else
                                <label for="abierta-{{ $pregunta['id'] }}" class="sr-only">Tu respuesta</label>
                                <textarea id="abierta-{{ $pregunta['id'] }}" name="respuestas[{{ $pregunta['id'] }}]" rows="4" maxlength="5000" class="w-full rounded-2xl border border-slate-200 p-3 text-sm focus:border-[#064C3D] focus:outline-none focus:ring-2 focus:ring-[#34D399]/40">{{ $valor }}</textarea>
                            @endif
                        </div>
                    </fieldset>
                @endforeach

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-6 py-3 text-sm font-bold text-white shadow-lg shadow-[#064C3D]/20 hover:bg-[#043A2F]"><i class="fas fa-paper-plane" aria-hidden="true"></i> Enviar prueba</button>
                </div>
            </form>

            @push('scripts')
                <script>
                    (() => {
                        const form = document.getElementById('form-prueba');
                        const token = document.querySelector('meta[name=csrf-token]').content;
                        const aviso = document.getElementById('guardado');
                        const total = {{ $total }};

                        const valor = (fieldset) => {
                            const marcado = fieldset.querySelector('input:checked');
                            if (marcado) return marcado.value;
                            const texto = fieldset.querySelector('textarea');
                            return texto ? texto.value.trim() : '';
                        };
                        const contar = () => {
                            const n = [...form.querySelectorAll('[data-pregunta]')].filter((f) => valor(f) !== '').length;
                            document.getElementById('respondidas').textContent = n;
                            document.getElementById('barra').style.width = (total ? Math.round(n * 100 / total) : 0) + '%';
                        };

                        // Guardado progresivo (HU-047, esc. 2)
                        const guardar = (fieldset) => fetch(@json(route('talentohumano.aprendiz.prueba.progreso')), {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                            body: JSON.stringify({ pregunta_id: Number(fieldset.dataset.pregunta), respuesta: valor(fieldset) }),
                        }).then((r) => r.ok ? r.json() : Promise.reject())
                          .then((d) => { aviso.textContent = 'Respuestas guardadas a las ' + d.guardada + '.'; })
                          .catch(() => { aviso.textContent = 'No se pudo guardar la última respuesta: revisa tu conexión. Se enviará con la prueba.'; });

                        form.querySelectorAll('[data-pregunta]').forEach((fieldset) => {
                            let espera;
                            fieldset.addEventListener('change', () => { contar(); guardar(fieldset); });
                            fieldset.querySelector('textarea')?.addEventListener('input', () => { contar(); clearTimeout(espera); espera = setTimeout(() => guardar(fieldset), 800); });
                        });

                        // Preguntas sin responder: no se envía y se lleva a la primera pendiente (HU-047, esc. 5)
                        form.addEventListener('submit', (e) => {
                            const pendiente = [...form.querySelectorAll('[data-pregunta]')].find((f) => valor(f) === '');
                            if (!pendiente) return;
                            e.preventDefault();
                            form.querySelectorAll('[data-pregunta]').forEach((f) => f.classList.remove('ring-2', 'ring-red-400'));
                            pendiente.classList.add('ring-2', 'ring-red-400');
                            pendiente.scrollIntoView({ behavior: 'smooth' });
                            aviso.textContent = 'Responda todas las preguntas antes de enviar';
                        });
                    })();
                </script>
            @endpush
    @endswitch
</x-talentohumano::layouts.aprendiz>
