<x-talentohumano::layouts.master title="Panel" :full-width="true">
    {{-- Bienvenida institucional (Hero) --}}
    <section class="folium-hero">
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

            {{-- Badges de Rol y Designaciones --}}
            <div class="entrada mt-5 flex flex-wrap gap-2" style="animation-delay: 180ms">
                @foreach ($roles as $rol)
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold ring-1 ring-white/20">
                        <i class="fas fa-id-badge text-[#34D399]" aria-hidden="true"></i> {{ $rol }}
                    </span>
                @endforeach

                @foreach ($designaciones as $desig)
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold ring-1 ring-white/20">
                        <i class="fas fa-user-tag text-[#34D399]" aria-hidden="true"></i> {{ $desig }}
                        <span class="font-normal text-emerald-100/80">· designación</span>
                    </span>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Contenido del Dashboard con los 6 módulos oficiales --}}
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
                <a href="{{ route('talentohumano.index') }}" class="mt-6 folium-btn folium-btn-primary">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i> Volver a la presentación
                </a>
            </div>
        @else
            {{-- Rejilla de los 6 módulos oficiales de FOLIUM --}}
            <div class="folium-panel-grid">
                @foreach ($secciones as $seccion)
                    @if (isset($seccion['ruta']) && Route::has($seccion['ruta']))
                        {{-- Módulo con funcionalidad disponible --}}
                        <a href="{{ route($seccion['ruta']) }}"
                           class="folium-module-card entrada group"
                           style="animation-delay: {{ 200 + $loop->index * 60 }}ms">
                            <div class="flex items-start justify-between gap-3">
                                <span class="folium-module-icon group-hover:bg-[#064C3D] group-hover:text-[#34D399] transition duration-300">
                                    <i class="fas {{ $seccion['icono'] }}" aria-hidden="true"></i>
                                </span>
                                <span class="folium-badge folium-badge-success">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Entrar
                                </span>
                            </div>
                            <h2 class="folium-module-title group-hover:text-[#064C3D] transition">{{ $seccion['titulo'] }}</h2>
                            <p class="folium-module-desc">{{ $seccion['texto'] }}</p>
                        </a>
                    @else
                        {{-- Módulos pendientes (Próximamente) --}}
                        <article class="folium-module-card entrada group"
                                 style="animation-delay: {{ 200 + $loop->index * 60 }}ms">
                            <div class="flex items-start justify-between gap-3">
                                <span class="folium-module-icon">
                                    <i class="fas {{ $seccion['icono'] }}" aria-hidden="true"></i>
                                </span>
                                <span class="folium-badge folium-badge-neutral">Próximamente</span>
                            </div>
                            <h2 class="folium-module-title">{{ $seccion['titulo'] }}</h2>
                            <p class="folium-module-desc">{{ $seccion['texto'] }}</p>
                        </article>
                    @endif
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
