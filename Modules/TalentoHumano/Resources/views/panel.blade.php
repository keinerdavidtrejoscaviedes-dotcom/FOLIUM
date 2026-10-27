<x-talentohumano::layouts.master title="Panel" :full-width="true">
    @push('styles')
        <style>
            .folium-dots { background-image: radial-gradient(rgba(255, 255, 255, .08) 1px, transparent 1px); background-size: 22px 22px; }
            @media (prefers-reduced-motion: no-preference) {
                .entrada { animation: entrada .6s cubic-bezier(.2, .8, .2, 1) both; }
            }
            @keyframes entrada { from { opacity: 0; transform: translateY(1rem); } to { opacity: 1; transform: none; } }
        </style>
    @endpush

    {{-- Bienvenida --}}
    <section class="relative isolate overflow-hidden bg-[#064C3D] text-white">
        <div class="folium-dots absolute inset-0 -z-10" aria-hidden="true"></div>
        <i class="fas fa-leaf absolute -right-8 -top-6 -z-10 rotate-12 text-[11rem] text-white/5" aria-hidden="true"></i>

        <div class="mx-auto max-w-6xl px-4 pb-24 pt-10 sm:px-6 sm:pt-14">
            <p class="entrada text-sm font-semibold uppercase tracking-[.2em] text-[#34D399]">FOLIUM · Talento Humano</p>
            <h1 class="entrada mt-3 text-3xl font-extrabold tracking-tight sm:text-4xl" style="animation-delay: 60ms">
                Hola, {{ $user->person->first_name ?? $user->full_name }}
            </h1>
            <p class="entrada mt-2 max-w-2xl text-emerald-50/85" style="animation-delay: 120ms">
                Este es tu espacio en FOLIUM. Aquí encontrarás las secciones del proceso de Talento Humano que corresponden a tu rol.
            </p>
            <div class="entrada mt-5 flex flex-wrap gap-2" style="animation-delay: 180ms">
                @forelse ($roles as $rol)
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold ring-1 ring-white/20">
                        <i class="fas fa-id-badge text-[#34D399]" aria-hidden="true"></i> {{ $rol }}
                    </span>
                @empty
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold ring-1 ring-white/20">
                        <i class="fas fa-circle-exclamation text-amber-300" aria-hidden="true"></i> Sin rol en Talento Humano
                    </span>
                @endforelse
            </div>
        </div>
    </section>

    {{-- Secciones del módulo --}}
    <section class="relative z-10 mx-auto -mt-14 max-w-6xl px-4 pb-16 sm:px-6">
        @if ($secciones->isEmpty())
            <div class="entrada rounded-3xl bg-white p-8 text-center shadow-xl shadow-[#064C3D]/10 ring-1 ring-slate-200/70 sm:p-12">
                <span class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-[#E8F3EF] text-2xl text-[#064C3D]">
                    <i class="fas fa-user-lock" aria-hidden="true"></i>
                </span>
                <h2 class="mt-5 text-xl font-bold text-[#064C3D]">Aún no tienes un rol en Talento Humano</h2>
                <p class="mx-auto mt-2 max-w-md text-slate-600">
                    Para usar FOLIUM, solicita al instructor líder de SENA Empresa que te asigne un rol en la aplicación Talento Humano.
                </p>
                <a href="{{ route('talentohumano.index') }}" class="mt-6 inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#043A2F]">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i> Volver a la presentación
                </a>
            </div>
        @else
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($secciones as $seccion)
                    <article class="entrada group relative flex h-full flex-col rounded-3xl bg-white p-6 shadow-lg shadow-[#064C3D]/5 ring-1 ring-slate-200/70 transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-[#064C3D]/10 hover:ring-[#34D399]/60"
                             style="animation-delay: {{ 200 + $loop->index * 60 }}ms">
                        <div class="flex items-start justify-between gap-3">
                            <span class="grid h-12 w-12 place-items-center rounded-2xl bg-[#E8F3EF] text-lg text-[#064C3D] transition duration-300 group-hover:bg-[#064C3D] group-hover:text-[#34D399]">
                                <i class="fas {{ $seccion['icono'] }}" aria-hidden="true"></i>
                            </span>
                            @if (isset($seccion['ruta']))
                                <span class="rounded-full bg-[#E8F3EF] px-3 py-1 text-xs font-semibold text-[#064C3D]">Abrir <i class="fas fa-arrow-right" aria-hidden="true"></i></span>
                            @else
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-500">Próximamente</span>
                            @endif
                        </div>
                        <h2 class="mt-5 font-bold text-slate-900">
                            @if (isset($seccion['ruta']))
                                {{-- El enlace cubre toda la tarjeta --}}
                                <a href="{{ route($seccion['ruta']) }}" class="after:absolute after:inset-0 after:rounded-3xl">{{ $seccion['titulo'] }}</a>
                            @else
                                {{ $seccion['titulo'] }}
                            @endif
                        </h2>
                        <p class="mt-1.5 text-sm leading-relaxed text-slate-600">{{ $seccion['texto'] }}</p>
                    </article>
                @endforeach
            </div>
        @endif

        <p class="mt-10 text-center text-sm text-slate-500">
            <a href="{{ route('talentohumano.index') }}" class="font-semibold text-[#064C3D] hover:underline">
                <i class="fas fa-seedling mr-1" aria-hidden="true"></i> Ver la presentación de FOLIUM
            </a>
        </p>
    </section>
</x-talentohumano::layouts.master>
