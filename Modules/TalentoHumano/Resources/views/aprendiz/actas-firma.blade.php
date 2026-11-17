<x-talentohumano::layouts.aprendiz title="Actas por firmar" activo="actas-firma" :p="$p">
    <div class="mb-6">
        <h1 class="text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">Actas por firmar</h1>
        <p class="mt-1 text-sm text-slate-600">Actas de recibimiento en las que firmas como jefe inmediato del cargo.</p>
    </div>

    @include('talentohumano::actas-firma._lista')
</x-talentohumano::layouts.aprendiz>
