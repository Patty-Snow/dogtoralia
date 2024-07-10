<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PetController;

use App\Http\Controllers\StaffController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\PetOwnerController;
use App\Http\Controllers\GeolocationController;
use App\Http\Controllers\BusinessOwnerController;
use App\Http\Controllers\StaffScheduleController;


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

    Route::put('update', [PetOwnerController::class, 'update']);
    Route::delete('delete', [PetOwnerController::class, 'destroy']);
    Route::get('trashed', [PetOwnerController::class, 'trashed']);
    Route::post('restore/{id}', [PetOwnerController::class, 'restore']);
    Route::post('force_delete/{id}', [PetOwnerController::class, 'forceDelete']);
    Route::get('/index', [PetOwnerController::class, 'index']);
});
Route::middleware(['checkAnyGuard:business_owner_api,staff_api'])->group(function () {
    Route::get('/pet_owners', [PetOwnerController::class, 'index']);
});
Route::middleware('checkAnyGuard:business_owner_api,staff_api,pet_owner_api')->group(function () {
    Route::get('/pet_owner/{id}', [PetOwnerController::class, 'show']);
});


// Rutas para pets (autenticación requerida para pet_owner_api)
Route::middleware('auth:pet_owner_api')->group(function () {
    Route::prefix('pets')->group(function () {
        Route::post('/', [PetController::class, 'store'])->name('pets.store');
        Route::get('/{id}', [PetController::class, 'show'])->name('pets.show');
        Route::put('/{id}', [PetController::class, 'update'])->name('pets.update');
        Route::delete('/{id}', [PetController::class, 'destroy'])->name('pets.destroy'); // Soft delete
        Route::put('/restore/{id}', [PetController::class, 'restore'])->name('pets.restore'); // Restaurar pet
        Route::delete('/delete/{id}', [PetController::class, 'forceDelete'])->name('pets.forceDelete'); // Restaurar pet
    });
});

// Rutas para listar pets (autenticación requerida para pet_owner_api, business_owner_api, staff_api)
Route::middleware(['checkAnyGuard:pet_owner_api,business_owner_api,staff_api'])->group(function () {
    Route::prefix('pets')->group(function () {
        Route::get('/index/{pet_owner_id}', [PetController::class, 'index'])->name('pets.index');
    });
});


//Rutas para Business Owner
Route::prefix('business_owner')->group(function () {
    Route::post('register', [BusinessOwnerController::class, 'register']);
    Route::post('login', [BusinessOwnerController::class, 'login']);


    Route::post('refresh', [BusinessOwnerController::class, 'refresh']);
    Route::post('logout', [BusinessOwnerController::class, 'logout']);

    Route::get('/{id}', [BusinessOwnerController::class, 'show']);
    Route::put('update', [BusinessOwnerController::class, 'update']);
    Route::delete('delete', [BusinessOwnerController::class, 'destroy']);

    Route::get('trashed', [BusinessOwnerController::class, 'trashed']);
    Route::post('restore/{id}', [BusinessOwnerController::class, 'restore']);
    Route::post('force_delete/{id}', [BusinessOwnerController::class, 'forceDelete']);
});


//Rutas para business



//Route::middleware('auth:business_owner_api')->group(function () {
Route::prefix('business')->group(function () {
    Route::get('/', [BusinessController::class, 'index']);
    Route::get('/all', [BusinessController::class, 'indexAll']);
    Route::post('register', [BusinessController::class, 'store']);
    Route::get('/{id}', [BusinessController::class, 'show']);
    Route::put('/{id}', [BusinessController::class, 'update']);
    Route::delete('/{id}', [BusinessController::class, 'destroy']);
});
// });


// Rutas para Staff
Route::prefix('staff')->group(function () {
    Route::post('register', [StaffController::class, 'register']);
    Route::post('login', [StaffController::class, 'login']);
    Route::post('refresh', [StaffController::class, 'refresh']);
    Route::post('logout', [StaffController::class, 'logout']);

    Route::get('/', [StaffController::class, 'index']);
    Route::get('/{id}', [StaffController::class, 'show']);
    Route::put('/{id}', [StaffController::class, 'update']);
    Route::delete('/{id}', [StaffController::class, 'destroy']);

    Route::get('trashed', [StaffController::class, 'trashed']);
    Route::post('restore/{id}', [StaffController::class, 'restore']);
    Route::post('force_delete/{id}', [StaffController::class, 'forceDelete']);
});


//Rutas para gestión de horario del Staff

Route::prefix('staff/schedules')->group(function () {
    Route::get('/index', [StaffScheduleController::class, 'index']);
    Route::post('/', [StaffScheduleController::class, 'store']);
    Route::put('/{id}', [StaffScheduleController::class, 'update']);
    Route::delete('/delete', [StaffScheduleController::class, 'destroy']);
});

//Rutas para formatear dirección a partir de coordenadas

Route::post('/get-address', [GeolocationController::class, 'getAddress']);