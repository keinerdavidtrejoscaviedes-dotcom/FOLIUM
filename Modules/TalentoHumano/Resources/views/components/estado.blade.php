@props(['valor', 'etiqueta' => null])
{{-- Etiqueta de color para los estados de pruebas, fases y cronogramas. --}}
@php
    $colores = [
        'borrador' => 'bg-slate-100 text-slate-600 ring-slate-200',
        'vigente' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'inactivo' => 'bg-slate-100 text-slate-500 ring-slate-200',
        'planeacion' => 'bg-sky-50 text-sky-700 ring-sky-200',
        'seleccion' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
        'empalme' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'en_curso' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'cerrada' => 'bg-slate-100 text-slate-500 ring-slate-200',
        'enviado' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'aprobado' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'rechazado' => 'bg-red-50 text-red-700 ring-red-200',
        'publicada' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'finalizada' => 'bg-slate-100 text-slate-500 ring-slate-200',
    ];
@endphp
<span {{ $attributes->class(['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1', $colores[$valor] ?? 'bg-slate-100 text-slate-600 ring-slate-200']) }}>
    {{ $etiqueta ?? ucfirst(str_replace('_', ' ', $valor)) }}
</span>
