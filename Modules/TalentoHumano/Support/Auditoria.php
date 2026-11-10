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

    /**
     * Acciones del proceso que habilita una designación, con las designaciones que las permiten en orden de preferencia.
     * Aunque quien las hace sea también Administrador, el origen del permiso es la designación (RN-036).
     */
    public const POR_DESIGNACION = [
        'cronograma_aprobado' => ['lider_estrategia'],
        'cronograma_rechazado' => ['lider_estrategia'],
        'reubicacion_registrada' => ['lider_estrategia'],
        'seleccion_validada' => ['instructor_th', 'lider_estrategia'],
        'seleccion_ajustada' => ['instructor_th', 'lider_estrategia'],
        'analisis_hv_guardado' => ['instructor_th', 'lider_estrategia'],
        'formato_entrevista_guardado' => ['lider_estrategia', 'instructor_th', 'instructor_tecnico'],
        'calificacion_registrada' => ['lider_estrategia', 'instructor_th', 'instructor_tecnico'],
        'acta_firmada' => ['instructor_th'],
    ];

    private const DESIGNACIONES = [
        'lider_estrategia' => 'Líder de la estrategia',
        'instructor_th' => 'Instructor de Talento Humano',
        'instructor_tecnico' => 'Instructor técnico',
    ];

    public static function registrar(string $evento, Model $modelo, array $anterior = [], array $nuevo = [], ?array $origen = null): void
    {
        self::insertar($evento, $modelo::class, (int) $modelo->getKey(), $anterior, $nuevo, $origen ?? self::origen($evento));
    }

    /**
     * Acción sobre un registro que no se carga como modelo (consultas con DB::table).
     */
    public static function registrarTipo(string $evento, string $tipo, int $id, array $nuevo = []): void
    {
        self::insertar($evento, $tipo, $id, [], $nuevo, self::origen($evento));
    }

    /**
     * Petición rechazada por la matriz de permisos: no ejecuta nada, pero queda el intento (HU-002, esc. 4).
     */
    public static function registrarIntentoDenegado(Request $request): void
    {
        self::insertar('acceso_denegado', self::ACCESO, 0, [], ['peticion' => $request->method() . ' /' . $request->path()], self::origen());
    }

    /**
     * Origen del permiso del usuario actual: [tipo, fuente]. Con el evento, las acciones de POR_DESIGNACION se atribuyen
     * a la designación vigente que las habilita.
     */
    public static function origen(?string $evento = null): array
    {
        $usuario = auth()->user();

        if ($usuario && isset(self::POR_DESIGNACION[$evento])) {
            $suyas = AccesoTH::designaciones($usuario);
            foreach (self::POR_DESIGNACION[$evento] as $tipo) {
                if (in_array($tipo, $suyas)) {
                    return ['Designación', self::DESIGNACIONES[$tipo]];
                }
            }
        }

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
