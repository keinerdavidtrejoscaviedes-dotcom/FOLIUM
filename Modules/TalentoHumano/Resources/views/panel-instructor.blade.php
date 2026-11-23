<x-talentohumano::layouts.instructor title="Panel">
    {{-- Encabezado --}}
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-semibold text-emerald-700">Hola, {{ $user->person->first_name ?? $user->full_name }}</p>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">Mi panel</h1>
            <p class="mt-1 text-sm text-slate-600">Este es tu espacio en FOLIUM. Aquí encontrarás las secciones del proceso de Talento Humano que corresponden a tu rol.</p>
        </div>
    </div>

    {{-- Avisos --}}
    @foreach ($avisos ?? [] as [$titulo, $texto, $enlace])
        <a href="{{ $enlace }}" class="mt-6 flex items-start gap-3 rounded-2xl bg-amber-50 p-4 text-sm text-amber-900 shadow-sm ring-1 ring-amber-200 hover:bg-amber-100" role="alert">
            <i class="fas fa-bell mt-0.5" aria-hidden="true"></i>
            <span><strong>{{ $titulo }}</strong><span class="block">{{ $texto }}</span></span>
        </a>
    @endforeach

    @if ($secciones->isEmpty())
        <div class="relative isolate mt-6 overflow-hidden rounded-3xl bg-[#064C3D] p-8 text-white sm:p-10">
            <div class="absolute inset-0 -z-10" style="background-image: radial-gradient(rgba(255,255,255,.08) 1px, transparent 1px); background-size: 22px 22px" aria-hidden="true"></div>
            <i class="fas fa-seedling absolute -bottom-6 -right-2 -z-10 text-[9rem] text-white/5" aria-hidden="true"></i>
            <span class="grid h-14 w-14 place-items-center rounded-2xl bg-white/10 text-2xl text-[#34D399] ring-1 ring-white/20">
                <i class="fas fa-user-lock" aria-hidden="true"></i>
            </span>
            <h2 class="mt-5 text-xl font-extrabold sm:text-2xl">Aún no tienes un rol en Talento Humano</h2>
            <p class="mt-2 max-w-xl text-emerald-50/85">
                Para usar FOLIUM, solicita al instructor líder de SENA Empresa que te asigne un rol en la aplicación Talento Humano.
            </p>
            <a href="{{ route('talentohumano.index') }}" class="mt-6 inline-flex items-center gap-2 rounded-full bg-[#34D399] px-4 py-2 text-sm font-semibold text-[#043A2F] hover:bg-emerald-300">
                <i class="fas fa-arrow-left" aria-hidden="true"></i> Volver a la presentación
            </a>
        </div>
    @else
        {{-- Secciones del rol --}}
        <ol class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($secciones as $seccion)
                @php $disponible = isset($seccion['ruta']) && Route::has($seccion['ruta']); @endphp
                <li>
                    @if ($disponible)
                        <a href="{{ route($seccion['ruta']) }}" class="group flex h-full items-center gap-4 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70 transition hover:ring-[#34D399]">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-[#064C3D] text-[#34D399]">
                                <i class="fas {{ $seccion['icono'] }}" aria-hidden="true"></i>
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-sm font-bold text-slate-800 group-hover:text-[#064C3D]">{{ $seccion['titulo'] }}</p>
                                    <span class="shrink-0 rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-800">Entrar</span>
                                </div>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $seccion['texto'] }}</p>
                            </div>
                        </a>
                    @else
                        <div class="flex h-full items-center gap-4 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-slate-100 text-slate-400">
                                <i class="fas {{ $seccion['icono'] }}" aria-hidden="true"></i>
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-sm font-bold text-slate-800">{{ $seccion['titulo'] }}</p>
                                    <span class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-500">Próximamente</span>
                                </div>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $seccion['texto'] }}</p>
                            </div>
                        </div>
                    @endif
                </li>
            @endforeach
        </ol>
    @endif

    <p class="mt-10 text-center text-sm text-slate-500">
        <a href="{{ route('talentohumano.index') }}" class="font-semibold text-[#064C3D] hover:underline">
            <i class="fas fa-seedling mr-1" aria-hidden="true"></i> Ver la presentación de FOLIUM
        </a>
    </p>
</x-talentohumano::layouts.instructor>
