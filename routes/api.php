<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PetOwnerController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

//Rutas para autenticación

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('refresh', [AuthController::class, 'refresh']);
Route::post('logout', [AuthController::class, 'logout']);

//Rutas para Pet Owner

Route::prefix('pet_owner')->group(function () {
    Route::post('register', [PetOwnerController::class, 'register']);
    Route::post('login', [PetOwnerController::class, 'login']);

    Route::middleware('auth:pet_owner_api')->group(function () {
        Route::post('refresh', [PetOwnerController::class, 'refresh']);
        Route::post('logout', [PetOwnerController::class, 'logout']);

        Route::get('me', [PetOwnerController::class, 'show']);
        Route::put('update', [PetOwnerController::class, 'update']);
    });
});
