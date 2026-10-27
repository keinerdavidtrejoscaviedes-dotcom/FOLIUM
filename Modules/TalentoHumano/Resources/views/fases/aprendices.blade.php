<x-talentohumano::layouts.master title="Aprendices de la ficha {{ $ficha->code }}">
    <x-talentohumano::encabezado titulo="Ficha {{ $ficha->code }}" :volver="route('talentohumano.fases.fichas.index', $fase->id)" hu="HU-017 · RF-018"
        subtitulo="{{ $ficha->programa }} · {{ $ficha->trimestre ? $ficha->trimestre . '.º trimestre' : 'sin iniciar' }} · {{ $aprendices->count() }} aprendices vinculados" />

    <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">#</th>
                    <th class="px-4 py-3">Documento</th>
                    <th class="px-4 py-3">Nombre</th>
                    <th class="px-4 py-3">Correo</th>
                    <th class="px-4 py-3">Estado en SICEFA</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($aprendices as $aprendiz)
                    <tr>
                        <td class="px-4 py-3 text-slate-400">{{ $loop->iteration }}</td>
                        <td class="px-4 py-3">{{ $aprendiz->document_number }}</td>
                        <td class="px-4 py-3 font-semibold text-slate-900">
                            {{ $aprendiz->first_last_name }} {{ $aprendiz->second_last_name }} {{ $aprendiz->first_name }}
                        </td>
                        <td class="px-4 py-3">{{ $aprendiz->misena_email ?: $aprendiz->personal_email ?: '—' }}</td>
                        <td class="px-4 py-3">{{ ucfirst(mb_strtolower($aprendiz->apprentice_status)) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-slate-500">Esta ficha no tiene aprendices en formación.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-talentohumano::layouts.master>
