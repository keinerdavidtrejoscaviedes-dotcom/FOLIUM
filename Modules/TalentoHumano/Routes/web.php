<?php

use Illuminate\Support\Facades\Route;
use Modules\TalentoHumano\Http\Controllers\AlertasController;
use Modules\TalentoHumano\Http\Controllers\AsignacionesController;
use Modules\TalentoHumano\Http\Controllers\AuditoriaController;
use Modules\TalentoHumano\Http\Controllers\CatalogoCargosController;
use Modules\TalentoHumano\Http\Controllers\DesignacionesController;
use Modules\TalentoHumano\Http\Controllers\InstructorLiderController;
use Modules\TalentoHumano\Http\Controllers\PlantillasActaController;
use Modules\TalentoHumano\Http\Controllers\TalentoHumanoController;
use Modules\TalentoHumano\Http\Middleware\AdministradorTH;
use Modules\TalentoHumano\Http\Middleware\SubmoduloLider;

/*
|--------------------------------------------------------------------------
| Rutas web del módulo Talento Humano (FOLIUM)
|--------------------------------------------------------------------------
| El prefijo /talento-humano coincide con la URL registrada para la app
| "Talento Humano" (id 28) en la tabla apps del ERP (BloqueAppSeeder).
*/

// Soporte para URL con mayúscula, como en otros módulos del ERP
Route::get('Talento-Humano', fn () => redirect()->route('talentohumano.index'));

Route::prefix('talento-humano')
    ->name('talentohumano.')
    ->group(function () {
        // Página de presentación de FOLIUM (pública)
        Route::get('/', [TalentoHumanoController::class, 'index'])->name('index');

        // Desde aquí empieza el módulo: requiere iniciar sesión
        Route::middleware('auth')->group(function () {
            Route::get('panel', [TalentoHumanoController::class, 'panel'])->name('panel');

            // Submódulo "Instructor Líder de TH": Administrador y Líder de la estrategia (HU-037)
            Route::prefix('lider')->name('lider.')->middleware(SubmoduloLider::class)->group(function () {
                Route::get('/', [InstructorLiderController::class, 'tablero'])->name('tablero');

                // Catálogo de cargos: lo consultan ambos; solo el Administrador lo modifica (HU-003 a HU-006, HU-037 esc. 8)
                Route::prefix('catalogo')->name('catalogo.')->controller(CatalogoCargosController::class)->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::middleware(AdministradorTH::class)->group(function () {
                        Route::get('nuevo', 'create')->name('create');
                        Route::post('/', 'store')->name('store');
                        Route::get('{cargo}/editar', 'edit')->whereNumber('cargo')->name('edit');
                        Route::put('{cargo}', 'update')->whereNumber('cargo')->name('update');
                        Route::patch('{cargo}/inactivar', 'inactivar')->whereNumber('cargo')->name('inactivar');
                        Route::patch('{cargo}/reactivar', 'reactivar')->whereNumber('cargo')->name('reactivar');
                        Route::delete('{cargo}', 'destroy')->whereNumber('cargo')->name('destroy');
                    });
                    Route::get('{cargo}', 'show')->whereNumber('cargo')->name('show');
                });

                // Designaciones de instructores: solo el Administrador las modifica (HU-009, HU-010)
                Route::prefix('designaciones')->name('designaciones.')->controller(DesignacionesController::class)->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::middleware(AdministradorTH::class)->group(function () {
                        Route::post('/', 'store')->name('store');
                        Route::post('{designacion}/reasignar', 'reasignar')->whereNumber('designacion')->name('reasignar');
                        Route::patch('{designacion}/retirar', 'retirar')->whereNumber('designacion')->name('retirar');
                    });
                });

                // Plantillas del acta de recibimiento del cargo: solo el Administrador las modifica (HU-007, HU-008)
                Route::prefix('plantillas')->name('plantillas.')->controller(PlantillasActaController::class)->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::middleware(AdministradorTH::class)->group(function () {
                        Route::get('nueva', 'create')->name('create');
                        Route::post('/', 'store')->name('store');
                        Route::get('{plantilla}/editar', 'edit')->whereNumber('plantilla')->name('edit');
                        Route::put('{plantilla}', 'update')->whereNumber('plantilla')->name('update');
                        Route::patch('{plantilla}/activar', 'activar')->whereNumber('plantilla')->name('activar');
                    });
                    Route::get('{plantilla}', 'show')->whereNumber('plantilla')->name('show');
                });

                // Asignaciones de cargo: consulta, suspensión y reactivación para ambos; carga inicial solo el Administrador (HU-012, HU-013, HU-016)
                Route::prefix('asignaciones')->name('asignaciones.')->controller(AsignacionesController::class)->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::patch('{asignacion}/suspender', 'suspender')->whereNumber('asignacion')->name('suspender');
                    Route::patch('{asignacion}/reactivar', 'reactivar')->whereNumber('asignacion')->name('reactivar');
                    Route::middleware(AdministradorTH::class)->group(function () {
                        Route::post('fase', 'registrarFase')->name('fase');
                        Route::get('inicial', 'create')->name('create');
                        Route::post('inicial', 'store')->name('store');
                        Route::get('carga-masiva', 'cargaMasiva')->name('carga-masiva');
                        Route::get('carga-masiva/plantilla', 'plantilla')->name('plantilla');
                        Route::post('carga-masiva/validar', 'validarCarga')->name('validar');
                        Route::post('carga-masiva/confirmar', 'confirmarCarga')->name('confirmar');
                        Route::post('carga-masiva/cancelar', 'cancelarCarga')->name('cancelar');
                        Route::get('carga-masiva/{carga}/rechazadas', 'rechazadas')->whereNumber('carga')->name('rechazadas');
                    });
                    Route::get('{asignacion}', 'show')->whereNumber('asignacion')->name('show');
                });

                // Alertas y servicios externos: solo el Administrador (HU-011, HU-015)
                Route::prefix('alertas')->name('alertas.')->controller(AlertasController::class)->middleware(AdministradorTH::class)->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::patch('leidas', 'leerTodas')->name('leer-todas');
                    Route::patch('{alerta}/leida', 'leer')->whereNumber('alerta')->name('leer');
                    Route::post('sicefa/probar', 'probarSicefa')->name('probar-sicefa');
                    Route::post('correos/{notificacion}/reintentar', 'reintentar')->whereNumber('notificacion')->name('reintentar');
                });

                // Auditoría: solo consulta; cualquier intento de modificarla se rechaza y se registra (HU-002)
                Route::prefix('auditoria')->name('auditoria.')->controller(AuditoriaController::class)->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('exportar', 'exportar')->name('exportar');
                    Route::match(['post', 'put', 'patch', 'delete'], '{registro?}', 'rechazarCambio')->name('rechazar');
                });

                Route::get('{seccion}', [InstructorLiderController::class, 'seccion'])
                    ->whereIn('seccion', InstructorLiderController::enConstruccion())
                    ->name('seccion');
            });
        });
    });
