<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Pet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\UnauthorizedException;

class PetController extends Controller
{
    public function index($pet_owner_id)
    {
        try {
            if (Auth::guard('pet_owner_api')->check() && Auth::id() == $pet_owner_id) {
                // Si el usuario es un pet owner y está accediendo a sus propias mascotas
                $pets = Pet::where('pet_owner_id', $pet_owner_id)->get();
                return response()->json($pets);
            } elseif (Auth::guard('business_owner_api')->check() || Auth::guard('staff_api')->check()) {
                // Si el usuario es business owner o staff, permitir acceso a todas las mascotas
                $pets = Pet::withTrashed()
                            ->where('pet_owner_id', $pet_owner_id)
                            ->get();
                return response()->json($pets);
            } else {
                // Otros casos, lanzar excepción de no autorizado
                throw new UnauthorizedException('Unauthorized access.');
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error fetching pets: ' . $e->getMessage()], 500);
        }
    }


    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'species' => 'required|string|max:255',
                'breed' => 'nullable|string|max:255',
                'birth_date' => 'nullable|date_format:d-m-Y', // Validar el formato de la fecha como día-mes-año
                'color' => 'nullable|string|max:255',
                'gender' => 'nullable|string|max:255',
            ]);

            // Convertir la fecha al formato Y-m-d antes de guardar
            if (isset($validatedData['birth_date'])) {
                $validatedData['birth_date'] = Carbon::createFromFormat('d-m-Y', $validatedData['birth_date'])->format('Y-m-d');
            }

            // Agregar el pet_owner_id del usuario autenticado
            $validatedData['pet_owner_id'] = Auth::id();

            $pet = Pet::create($validatedData);

            return response()->json($pet, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error creating pet: ' . $e->getMessage()], 500);
        }
    }


    public function show($pet_id)
    {
        try {
            if (Auth::guard('pet_owner_api')->check()) {
                // Obtener la mascota por su ID, incluyendo las eliminadas
                $pet = Pet::withTrashed()->findOrFail($pet_id);

                // Verificar si el pet pertenece al pet owner autenticado
                if ($pet->pet_owner_id != Auth::id()) {
                    throw new UnauthorizedException('You do not have permission to access this resource.');
                }

                return response()->json($pet);
            } elseif (Auth::guard('business_owner_api')->check() || Auth::guard('staff_api')->check()) {
                // Si el usuario es business owner o staff, permitir acceso a todas las mascotas
                $pet = Pet::withTrashed()->findOrFail($pet_id);

                return response()->json($pet);
            } else {
                // Otros casos, lanzar excepción de no autorizado
                throw new UnauthorizedException('Unauthorized access.');
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error fetching pet: ' . $e->getMessage()], 500);
        }
    }


    public function update(Request $request, $id)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'sometimes|string|max:255',
                'species' => 'sometimes|string|max:255',
                'breed' => 'sometimes|string|max:255',
                'birth_date' => 'sometimes|date_format:d-m-Y', // Validar el formato de la fecha como día-mes-año
                'color' => 'sometimes|string|max:255',
                'gender' => 'sometimes|string|max:255',
            ]);

            // Convertir la fecha al formato Y-m-d antes de guardar
            if (isset($validatedData['birth_date'])) {
                $validatedData['birth_date'] = Carbon::createFromFormat('d-m-Y', $validatedData['birth_date'])->format('Y-m-d');
            }

            // Verificar que el pet pertenece al pet owner autenticado
            $pet = Pet::withTrashed()->where('id', $id)
                ->where('pet_owner_id', Auth::id())
                ->first();

            if (!$pet) {
                return response()->json(['error' => 'Pet not found or you do not have permission to update this pet.'], 404);
            }

            // Actualizar los datos del pet
            $pet->update($validatedData);

            return response()->json($pet);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error updating pet: ' . $e->getMessage()], 500);
        }
    }


    public function destroy($id)
    {
        try {
            // Verificar que el pet pertenece al pet owner autenticado
            $pet = Pet::where('id', $id)
                ->where('pet_owner_id', Auth::id())
                ->first();

            if (!$pet) {
                return response()->json(['error' => 'Pet not found or you do not have permission to delete this pet.'], 404);
            }

            // Eliminar el pet (soft delete)
            $pet->delete();

            return response()->json(['message' => 'Pet deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error deleting pet: ' . $e->getMessage()], 500);
        }
    }

    public function trashed()
    {
        try {
            if (Auth::guard('business_owner_api')->check() || Auth::guard('staff_api')->check()) {
                // Permitir acceso a las mascotas eliminadas solo para business owners y staff
                $pets = Pet::onlyTrashed()->get();
                return response()->json($pets);
            } else {
                throw new UnauthorizedException('Unauthorized access.');
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error fetching trashed pets: ' . $e->getMessage()], 500);
        }
    }

    public function restore($pet_id)
    {
        try {
            // Verificar si el usuario es business owner o staff
            if (Auth::guard('business_owner_api')->check() || Auth::guard('staff_api')->check()) {
                $pet = Pet::withTrashed()->findOrFail($pet_id);
                $pet->restore();
                return response()->json(['message' => 'Pet restored successfully']);
            } else {
                throw new UnauthorizedException('Unauthorized access.');
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error restoring pet: ' . $e->getMessage()], 500);
        }
    }

    public function forceDelete($pet_id)
    {
        try {
            // Verificar si el usuario es business owner o staff
            if (Auth::guard('business_owner_api')->check() || Auth::guard('staff_api')->check()) {
                $pet = Pet::withTrashed()->findOrFail($pet_id);
                $pet->forceDelete();
                return response()->json(['message' => 'Pet permanently deleted']);
            } else {
                throw new UnauthorizedException('Unauthorized access.');
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error deleting pet permanently: ' . $e->getMessage()], 500);
        }
    }
}
