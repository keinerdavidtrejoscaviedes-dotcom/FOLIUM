<?php

use Illuminate\Support\Facades\Route;
use Modules\TalentoHumano\Http\Controllers\TalentoHumanoController;

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

            // Submódulo de Activación de Cargos y Fichas (HU-018 y HU-019)
            Route::prefix('activacion-cargos')->name('activacion.')->group(function () {
                Route::get('/', [\Modules\TalentoHumano\Http\Controllers\ActivacionCargoController::class, 'index'])->name('index');
                Route::post('/toggle', [\Modules\TalentoHumano\Http\Controllers\ActivacionCargoController::class, 'toggleCargo'])->name('toggle');
                Route::post('/cupos', [\Modules\TalentoHumano\Http\Controllers\ActivacionCargoController::class, 'updateCupos'])->name('cupos');
                Route::post('/area', [\Modules\TalentoHumano\Http\Controllers\ActivacionCargoController::class, 'activarArea'])->name('area');
                Route::post('/fichas/agregar', [\Modules\TalentoHumano\Http\Controllers\ActivacionCargoController::class, 'agregarFicha'])->name('fichas.agregar');
                Route::delete('/fichas/{id}', [\Modules\TalentoHumano\Http\Controllers\ActivacionCargoController::class, 'eliminarFicha'])->name('fichas.eliminar');
                Route::post('/fichas/asignar-programa', [\Modules\TalentoHumano\Http\Controllers\ActivacionCargoController::class, 'asignarPrograma'])->name('fichas.asignar-programa');
            });

            // Submódulo de Postulaciones y Análisis de Hojas de Vida (HU-024 · Mockup INS-05)
            Route::prefix('postulaciones')->name('postulaciones.')->group(function () {
                Route::get('/', [\Modules\TalentoHumano\Http\Controllers\PostulacionController::class, 'index'])->name('index');
                Route::get('/{id}', [\Modules\TalentoHumano\Http\Controllers\PostulacionController::class, 'show'])->name('show');
                Route::post('/{id}/analisis', [\Modules\TalentoHumano\Http\Controllers\PostulacionController::class, 'guardarAnalisis'])->name('analisis');
            });

            // Submódulo de Entrevistas, Formato y Calificaciones (HU-040, HU-041, HU-042 · Mockup INS-07, INS-08)
            Route::prefix('entrevistas')->name('entrevistas.')->group(function () {
                Route::get('/', [\Modules\TalentoHumano\Http\Controllers\EntrevistaController::class, 'index'])->name('index');
                Route::get('/formato', [\Modules\TalentoHumano\Http\Controllers\EntrevistaController::class, 'formato'])->name('formato');
                Route::post('/formato', [\Modules\TalentoHumano\Http\Controllers\EntrevistaController::class, 'guardarFormato'])->name('formato.guardar');
                Route::get('/{entrevista}/calificar/{postulacion}', [\Modules\TalentoHumano\Http\Controllers\EntrevistaController::class, 'calificar'])->name('calificar');
                Route::post('/{entrevista}/calificar/{postulacion}', [\Modules\TalentoHumano\Http\Controllers\EntrevistaController::class, 'guardarCalificacion'])->name('calificar.guardar');
            });

            // Submódulo de Selección y actas (HU-038 · Mockup INS-01, INS-03, INS-04)
            Route::prefix('seleccion')->name('seleccion.')->group(function () {
                Route::get('/', [\Modules\TalentoHumano\Http\Controllers\SeleccionActasController::class, 'index'])->name('index');
                Route::get('/validar/{id}', [\Modules\TalentoHumano\Http\Controllers\SeleccionActasController::class, 'validarCargo'])->name('validar');
                Route::post('/validar/{id}', [\Modules\TalentoHumano\Http\Controllers\SeleccionActasController::class, 'guardarValidacion'])->name('validar.guardar');
                Route::get('/reubicaciones', [\Modules\TalentoHumano\Http\Controllers\SeleccionActasController::class, 'reubicaciones'])->name('reubicaciones');
                Route::post('/reubicaciones', [\Modules\TalentoHumano\Http\Controllers\SeleccionActasController::class, 'guardarReubicacion'])->name('reubicaciones.guardar');
            });

            // Submódulo de Empalmes (RF-013 · Acta de recibimiento, inventario y firmas)
            Route::prefix('empalmes')->name('empalmes.')->group(function () {
                Route::get('/', [\Modules\TalentoHumano\Http\Controllers\EmpalmeController::class, 'index'])->name('index');
                Route::get('/{id}', [\Modules\TalentoHumano\Http\Controllers\EmpalmeController::class, 'show'])->name('show');
                Route::post('/{id}/item/{itemId}/toggle', [\Modules\TalentoHumano\Http\Controllers\EmpalmeController::class, 'toggleItem'])->name('items.toggle');
                Route::post('/{id}/items', [\Modules\TalentoHumano\Http\Controllers\EmpalmeController::class, 'agregarItem'])->name('items.agregar');
                Route::post('/{id}/firmar', [\Modules\TalentoHumano\Http\Controllers\EmpalmeController::class, 'firmarActa'])->name('firmar');
                Route::post('/{id}/visto-bueno', [\Modules\TalentoHumano\Http\Controllers\EmpalmeController::class, 'vistoBueno'])->name('visto-bueno');
            });

            // Submódulo de Catálogo de Cargos (RF-019 · Manual de funciones y responsabilidades SIG)
            Route::prefix('catalogo')->name('catalogo.')->group(function () {
                Route::get('/', [\Modules\TalentoHumano\Http\Controllers\CatalogoCargoController::class, 'index'])->name('index');
                Route::get('/{id}', [\Modules\TalentoHumano\Http\Controllers\CatalogoCargoController::class, 'show'])->name('show');
            });
        });
    });
