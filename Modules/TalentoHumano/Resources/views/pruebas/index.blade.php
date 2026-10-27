<x-talentohumano::layouts.master title="Pruebas psicotécnicas">
    <x-talentohumano::encabezado titulo="Pruebas psicotécnicas" :volver="route('talentohumano.panel')" hu="HU-039 · RF-007"
        subtitulo="Diseñe las pruebas que los postulantes presentan dentro de FOLIUM. Una prueba publicada (vigente) es la que el Gestor de Talento Humano puede habilitar a los postulantes.">
        <a href="{{ route('talentohumano.pruebas.create') }}" class="inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#043A2F]">
            <i class="fas fa-plus" aria-hidden="true"></i> Nueva prueba
        </a>
    </x-talentohumano::encabezado>

    <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Título</th>
                    <th class="px-4 py-3">Versión</th>
                    <th class="px-4 py-3">Preguntas</th>
                    <th class="px-4 py-3">Puntaje máximo</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3">Autor</th>
                    <th class="px-4 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($pruebas as $prueba)
                    <tr class="hover:bg-slate-50/60">
                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $prueba->titulo }}</td>
                        <td class="px-4 py-3">v{{ $prueba->version }}</td>
                        <td class="px-4 py-3">{{ $prueba->preguntas }}</td>
                        <td class="px-4 py-3">{{ rtrim(rtrim(number_format($prueba->puntaje, 2, ',', '.'), '0'), ',') }}</td>
                        <td class="px-4 py-3"><x-talentohumano::estado :valor="$prueba->estado" /></td>
                        <td class="px-4 py-3">{{ $prueba->first_name }} {{ $prueba->first_last_name }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <a href="{{ route('talentohumano.pruebas.show', $prueba->id) }}" class="rounded-full px-3 py-1.5 font-semibold text-[#064C3D] hover:bg-[#E8F3EF]">
                                <i class="fas fa-eye" aria-hidden="true"></i> Ver
                            </a>
                            <a href="{{ route('talentohumano.pruebas.edit', $prueba->id) }}" class="rounded-full px-3 py-1.5 font-semibold text-[#064C3D] hover:bg-[#E8F3EF]">
                                <i class="fas fa-pen" aria-hidden="true"></i> Editar
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-slate-500">
                            Aún no hay pruebas psicotécnicas.
                            <a href="{{ route('talentohumano.pruebas.create') }}" class="font-semibold text-[#064C3D] hover:underline">Cree la primera</a>.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-talentohumano::layouts.master>
