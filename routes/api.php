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


Route::post('register', [cartasController::class, 'register']);
Route::post('login', [cartasController::class, 'login']);
Route::post('passRecovery', [cartasController::class, 'passRecovery']);

Route::post('buscarAnuncio', [cartasController::class, 'buscarAnuncio']);

Route::middleware(["isloggedMiddleware"])->group(function () {
    Route::post('crearVenta', [cartasController::class, 'crearVenta']);
    Route::post('buscarCartas', [cartasController::class, 'buscarCartas']);
});

Route::middleware(["isAdminMiddleware"])->group(function () {
    Route::post('crearCarta', [cartasController::class, 'crearCarta']);
    Route::post('crearColecion', [cartasController::class, 'crearColecion']);
    Route::post('asociarCartaColeccion', [cartasController::class, 'asociarCartaColeccion']);
});