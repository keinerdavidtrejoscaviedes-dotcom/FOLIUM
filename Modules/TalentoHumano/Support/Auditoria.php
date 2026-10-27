<?php

namespace Modules\TalentoHumano\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Registra acciones en la tabla audits del ERP, con el mismo formato de owen-it/laravel-auditing.
 * El paquete no está instalado (app/Support/AuditableShim.php), así que FOLIUM escribe los registros directamente.
 *
 * Cada registro guarda el origen del permiso que habilitó la acción (RN-036) en la columna tags,
 * con la forma "folium|<tipo>|<fuente>"; por ejemplo "folium|Designación|Líder de la estrategia".
 */
class Auditoria
{
    public const ETIQUETA = 'folium';

    /** Tipo de registro para las peticiones rechazadas por falta de permisos (HU-002, esc. 4). */
    public const ACCESO = 'FOLIUM\\Acceso';

    public static function registrar(string $evento, Model $modelo, array $anterior = [], array $nuevo = [], ?array $origen = null): void
    {
        self::insertar($evento, $modelo::class, (int) $modelo->getKey(), $anterior, $nuevo, $origen ?? self::origen());
    }

    /**
     * Acción sobre un registro que no se carga como modelo (consultas con DB::table).
     */
    public static function registrarTipo(string $evento, string $tipo, int $id, array $nuevo = []): void
    {
        self::insertar($evento, $tipo, $id, [], $nuevo, self::origen());
    }

    /**
     * Petición rechazada por la matriz de permisos: no ejecuta nada, pero queda el intento (HU-002, esc. 4).
     */
    public static function registrarIntentoDenegado(Request $request): void
    {
        self::insertar('acceso_denegado', self::ACCESO, 0, [], ['peticion' => $request->method() . ' /' . $request->path()], self::origen());
    }

    /**
     * Origen del permiso del usuario actual: [tipo, fuente].
     */
    public static function origen(): array
    {
        $usuario = auth()->user();

        return match (true) {
            !$usuario => ['Sistema', 'Sistema'],
            AccesoTH::esAdministrador($usuario) => ['Rol', 'Administrador'],
            AccesoTH::esLiderEstrategia($usuario) => ['Designación', 'Líder de la estrategia'],
            in_array('instructor_th', AccesoTH::designaciones($usuario)) => ['Designación', 'Instructor de Talento Humano'],
            in_array('instructor_tecnico', AccesoTH::designaciones($usuario)) => ['Designación', 'Instructor técnico'],
            default => ['Rol', $usuario->roles->first(fn ($r) => Str::startsWith($r->slug, 'talentohumano.'))?->name ?? 'Sin permisos en FOLIUM'],
        };
    }

    /**
     * Origen guardado en un registro: [tipo, fuente].
     */
    public static function origenDe(?string $tags): array
    {
        $partes = explode('|', (string) $tags);

        return [$partes[1] ?? 'Sistema', $partes[2] ?? ''];
    }

    private static function insertar(string $evento, string $tipo, int $id, array $anterior, array $nuevo, array $origen): void
    {
        $request = request();

        DB::table('audits')->insert([
            'user_type' => auth()->check() ? User::class : null,
            'user_id' => auth()->id(),
            'event' => $evento,
            'auditable_type' => $tipo,
            'auditable_id' => $id,
            'old_values' => json_encode($anterior, JSON_UNESCAPED_UNICODE),
            'new_values' => json_encode($nuevo, JSON_UNESCAPED_UNICODE),
            'url' => $request?->fullUrl(),
            'ip_address' => $request?->ip(),
            'user_agent' => mb_substr((string) $request?->userAgent(), 0, 1023),
            'tags' => mb_substr(implode('|', [self::ETIQUETA, ...$origen]), 0, 255),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
