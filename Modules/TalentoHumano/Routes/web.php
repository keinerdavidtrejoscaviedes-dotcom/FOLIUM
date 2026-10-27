<?php

use Illuminate\Support\Facades\Route;
use Modules\TalentoHumano\Http\Controllers\ActivacionCargoController;
use Modules\TalentoHumano\Http\Controllers\CronogramaController;
use Modules\TalentoHumano\Http\Controllers\FaseController;
use Modules\TalentoHumano\Http\Controllers\FichaFaseController;
use Modules\TalentoHumano\Http\Controllers\FormatoEntrevistaController;
use Modules\TalentoHumano\Http\Controllers\PruebaPsicotecnicaController;
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

            // Psicólogo: pruebas psicotécnicas (HU-039)
            Route::prefix('pruebas-psicotecnicas')->name('pruebas.')->controller(PruebaPsicotecnicaController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('crear', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('preguntas/{pregunta}/imagen', 'imagen')->whereNumber('pregunta')->name('imagen');
                Route::get('{prueba}', 'show')->whereNumber('prueba')->name('show');
                Route::get('{prueba}/editar', 'edit')->whereNumber('prueba')->name('edit');
                Route::put('{prueba}', 'update')->whereNumber('prueba')->name('update');
                Route::patch('{prueba}/estado', 'estado')->whereNumber('prueba')->name('estado');
            });

            // Evaluadores (psicólogo e instructores): formato de entrevista propio (HU-040)
            Route::get('formato-entrevista', [FormatoEntrevistaController::class, 'edit'])->name('formato.edit');
            Route::put('formato-entrevista', [FormatoEntrevistaController::class, 'update'])->name('formato.update');

            // Gestor de TH: preparación de la fase (HU-017, HU-019, HU-020)
            Route::prefix('fases')->name('fases.')->group(function () {
                Route::get('/', [FaseController::class, 'index'])->name('index');
                Route::post('/', [FaseController::class, 'store'])->name('store');

                Route::prefix('{fase}')->whereNumber('fase')->group(function () {
                    Route::get('/', [FaseController::class, 'show'])->name('show');

                    Route::get('fichas', [FichaFaseController::class, 'index'])->name('fichas.index');
                    Route::put('fichas', [FichaFaseController::class, 'update'])->name('fichas.update');
                    Route::get('fichas/{ficha}/aprendices', [FichaFaseController::class, 'aprendices'])->whereNumber('ficha')->name('fichas.aprendices');

                    Route::get('cargos', [ActivacionCargoController::class, 'edit'])->name('cargos.edit');
                    Route::put('cargos', [ActivacionCargoController::class, 'update'])->name('cargos.update');

                    Route::get('cronograma', [CronogramaController::class, 'edit'])->name('cronograma.edit');
                    Route::put('cronograma', [CronogramaController::class, 'update'])->name('cronograma.update');
                });
            });
        });
    });
