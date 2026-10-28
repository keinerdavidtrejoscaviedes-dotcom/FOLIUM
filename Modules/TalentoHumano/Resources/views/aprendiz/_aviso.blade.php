{{-- Aviso de pantalla vacía o bloqueada: icono, título, texto y, si aplica, un enlace. --}}
<div class="entrada rounded-3xl bg-white p-8 text-center shadow-sm ring-1 ring-slate-200/70 sm:p-12">
    <span class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-[#E8F3EF] text-2xl text-[#064C3D]"><i class="fas {{ $icono ?? 'fa-hourglass-half' }}" aria-hidden="true"></i></span>
    <h2 class="mt-5 text-xl font-bold text-[#064C3D]">{{ $titulo }}</h2>
    @isset($texto)
        <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-slate-600">{{ $texto }}</p>
    @endisset
    @isset($enlace)
        <a href="{{ $enlace[0] }}" class="mt-6 inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-[#064C3D]/20 hover:bg-[#043A2F]">
            {{ $enlace[1] }} <i class="fas fa-arrow-right" aria-hidden="true"></i>
        </a>
    @endisset
</div>
