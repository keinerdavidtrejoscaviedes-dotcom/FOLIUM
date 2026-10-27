<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Aviso para el Administrador de FOLIUM (HU-015, RN-031).
 */
class Alerta extends Model
{
    /** Ícono y color de cada tipo de alerta. */
    public const ESTILOS = [
        'asignacion_suspendida' => ['fa-pause', 'bg-amber-50 text-amber-600'],
        'asignacion_reactivada' => ['fa-play', 'bg-emerald-50 text-emerald-700'],
        'carga_masiva' => ['fa-file-excel', 'bg-[#E8F3EF] text-[#064C3D]'],
        'sicefa_caido' => ['fa-plug-circle-xmark', 'bg-red-50 text-red-600'],
        'sicefa_recuperado' => ['fa-plug-circle-check', 'bg-[#E8F3EF] text-[#064C3D]'],
        'correo_no_entregado' => ['fa-envelope-circle-check', 'bg-red-50 text-red-600'],
    ];

    protected $table = 'th_alertas';

    protected $fillable = ['tipo', 'titulo', 'texto', 'enlace', 'leida_en'];

    protected $casts = ['leida_en' => 'datetime'];

    public static function crear(string $tipo, string $titulo, ?string $texto = null, ?string $enlace = null): self
    {
        return self::create(compact('tipo', 'titulo', 'texto', 'enlace'));
    }

    public function scopeSinLeer($query)
    {
        return $query->whereNull('leida_en');
    }

    public function estilo(): array
    {
        return self::ESTILOS[$this->tipo] ?? ['fa-bell', 'bg-slate-100 text-slate-600'];
    }
}
