<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\cartasController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::put('register', [cartasController::class, 'register']);
Route::put('login', [cartasController::class, 'login']);
Route::put('passRecovery', [cartasController::class, 'passRecovery']);

Route::put('buscarAnuncio', [cartasController::class, 'buscarAnuncio']);

Route::middleware(["isloggedMiddleware"])->group(function () {
    Route::put('crearVenta', [cartasController::class, 'crearVenta']);
    Route::put('buscarCartas', [cartasController::class, 'buscarCartas']);
});

Route::middleware(["isAdminMiddleware"])->group(function () {
    Route::put('crearCarta', [cartasController::class, 'crearCarta']);
    Route::put('crearColecion', [cartasController::class, 'crearColecion']);
    Route::put('asociarCartaColeccion', [cartasController::class, 'asociarCartaColeccion']);
});