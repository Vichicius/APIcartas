<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\cartasController;
use App\Http\Controllers\UsuariosController;
use App\Http\Controllers\VentasController;

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


Route::put('register', [UsuariosController::class, 'register']);
Route::put('login', [UsuariosController::class, 'login']);
Route::put('passRecovery', [UsuariosController::class, 'passRecovery']);

Route::put('buscarAnuncio', [VentasController::class, 'buscarAnuncio']);

Route::middleware(["isloggedMiddleware"])->group(function () {
    Route::put('crearVenta', [VentasController::class, 'crearVenta']);
    Route::put('buscarCartas', [VentasController::class, 'buscarCartas']);
});

Route::middleware(["isAdminMiddleware"])->group(function () {
    Route::put('crearCarta', [cartasController::class, 'crearCarta']);
    Route::put('crearColecion', [cartasController::class, 'crearColecion']);
    Route::put('asociarCartaColeccion', [cartasController::class, 'asociarCartaColeccion']);
});
