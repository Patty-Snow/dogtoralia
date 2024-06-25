<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PetOwnerController;
use App\Http\Controllers\BusinessOwnerController;

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
        Route::delete('delete', [PetOwnerController::class, 'destroy']);
    });
});

//Rutas para Business Owner
Route::prefix('business_owner')->group(function () {
    Route::post('register', [BusinessOwnerController::class, 'register']);
    Route::post('login', [BusinessOwnerController::class, 'login']);

    Route::middleware('auth:business_owner_api')->group(function () {
        Route::post('refresh', [BusinessOwnerController::class, 'refresh']);
        Route::post('logout', [BusinessOwnerController::class, 'logout']);

        Route::get('me', [BusinessOwnerController::class, 'show']);
        Route::put('update', [BusinessOwnerController::class, 'update']);
        Route::delete('delete', [BusinessOwnerController::class, 'destroy']);
    });
});


//Rutas para crud de negocio

use App\Http\Controllers\BusinessController;

//Route::middleware('auth:business_owner_api')->group(function () {
    Route::prefix('business')->group(function () {
    Route::get('/', [BusinessController::class, 'index']);
    Route::post('register', [BusinessController::class, 'store']);
    Route::get('/{id}', [BusinessController::class, 'show']);
    Route::put('/{id}', [BusinessController::class, 'update']);
    Route::delete('/{id}', [BusinessController::class, 'destroy']);
    });
// });
