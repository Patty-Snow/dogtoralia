<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

use App\Http\Controllers\PetOwnerController;
use App\Http\Controllers\BusinessOwnerController;
use App\Http\Controllers\StylistVeterinarianController;
use App\Http\Controllers\BusinessController;

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



//Rutas para Pet Owner

Route::prefix('pet_owner')->group(function () {
    Route::post('register', [PetOwnerController::class, 'register']);
    Route::post('login', [PetOwnerController::class, 'login']);
    Route::post('refresh', [PetOwnerController::class, 'refresh']);
    Route::post('logout', [PetOwnerController::class, 'logout']);

    Route::get('me', [PetOwnerController::class, 'show']);
    Route::put('update', [PetOwnerController::class, 'update']);
    Route::delete('delete', [PetOwnerController::class, 'destroy']);
    Route::get('trashed', [PetOwnerController::class, 'trashed']);
    Route::post('restore/{id}', [PetOwnerController::class, 'restore']);
    Route::post('force_delete/{id}', [PetOwnerController::class, 'forceDelete']);
});

//Rutas para Business Owner
Route::prefix('business_owner')->group(function () {
    Route::post('register', [BusinessOwnerController::class, 'register']);
    Route::post('login', [BusinessOwnerController::class, 'login']);


    Route::post('refresh', [BusinessOwnerController::class, 'refresh']);
    Route::post('logout', [BusinessOwnerController::class, 'logout']);

    Route::get('me', [BusinessOwnerController::class, 'show']);
    Route::put('update', [BusinessOwnerController::class, 'update']);
    Route::delete('delete', [BusinessOwnerController::class, 'destroy']);

    Route::get('trashed', [BusinessOwnerController::class, 'trashed']);
    Route::post('restore/{id}', [BusinessOwnerController::class, 'restore']);
    Route::post('force_delete/{id}', [BusinessOwnerController::class, 'forceDelete']);
});


//Rutas para crud de negocio



//Route::middleware('auth:business_owner_api')->group(function () {
Route::prefix('business')->group(function () {
    Route::get('/', [BusinessController::class, 'index']);
    Route::post('register', [BusinessController::class, 'store']);
    Route::get('/{id}', [BusinessController::class, 'show']);
    Route::put('/{id}', [BusinessController::class, 'update']);
    Route::delete('/{id}', [BusinessController::class, 'destroy']);
});
// });

// Rutas para Stylist Veterinarian
Route::prefix('stylist_veterinarian')->group(function () {
    Route::post('register', [StylistVeterinarianController::class, 'register']);
    Route::post('login', [StylistVeterinarianController::class, 'login']);
    Route::post('refresh', [StylistVeterinarianController::class, 'refresh']);
    Route::post('logout', [StylistVeterinarianController::class, 'logout']);
    Route::get('me', [StylistVeterinarianController::class, 'show']);

    Route::get('/', [StylistVeterinarianController::class, 'index']);
    Route::get('/{id}', [StylistVeterinarianController::class, 'show']);
    Route::put('/{id}', [StylistVeterinarianController::class, 'update']);
    Route::delete('/{id}', [StylistVeterinarianController::class, 'destroy']);

    Route::get('trashed', [StylistVeterinarianController::class, 'trashed']);
    Route::post('restore/{id}', [StylistVeterinarianController::class, 'restore']);
    Route::post('force_delete/{id}', [StylistVeterinarianController::class, 'forceDelete']);
});
