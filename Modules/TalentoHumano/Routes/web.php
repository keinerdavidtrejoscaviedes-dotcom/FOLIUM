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
        });
    });
