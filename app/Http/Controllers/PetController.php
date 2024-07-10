<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Pet;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
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
        DB::beginTransaction();

        try {
            // Validación de la solicitud
            $validatedData = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('pets')->where(function ($query) {
                        return $query->where('pet_owner_id', Auth::id());
                    }),
                ],
                'species' => 'required|string|max:255',
                'breed' => 'nullable|string|max:255',
                'birth_date' => 'nullable|date_format:d-m-Y',
                'color' => 'nullable|string|max:255',
                'gender' => 'nullable|string|max:255',
                'image' => 'required|image|max:2028',
                'alt_text' => 'nullable|string|max:255'
            ]);

            // Convertir la fecha de nacimiento al formato Y-m-d
            if (!empty($validatedData['birth_date'])) {
                $validatedData['birth_date'] = Carbon::createFromFormat('d-m-Y', $validatedData['birth_date'])->format('Y-m-d');
            }

            // Manejo de la carga de imagen
            if ($request->hasFile('image')) {
                $fileName = time() . '_' . $request->image->getClientOriginalName();
                $filePath = $request->image->storeAs('pet_images', $fileName, 'public');

                $image = new Image();
                $image->source_url = 'storage/' . $filePath;
                $image->alt_text = $request->alt_text;
                $image->save();

                // Asignar el ID de la imagen a la mascota
                $validatedData['photo_id'] = $image->id;
            }

            // Agregar el pet_owner_id del usuario autenticado
            $validatedData['pet_owner_id'] = Auth::id();

            // Crear la mascota
            $pet = Pet::create($validatedData);

            // Formatear la fecha de nacimiento para la respuesta JSON
            $pet->birth_date = Carbon::parse($pet->birth_date)->format('d-m-Y');

            // Confirmar la transacción
            DB::commit();

            // Devolver la respuesta JSON con la mascota creada
            return response()->json($pet, 201);
        } catch (\Exception $e) {
            // Revertir la transacción
            DB::rollBack();

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
        DB::beginTransaction();

        try {
            // Validación de la solicitud
            $validatedData = $request->validate([
                'name' => [
                    'sometimes',
                    'string',
                    'max:255',
                    Rule::unique('pets')->where(function ($query) {
                        return $query->where('pet_owner_id', Auth::id());
                    }),
                ],
                'species' => 'sometimes|string|max:255',
                'breed' => 'sometimes|string|max:255',
                'birth_date' => 'sometimes|date_format:d-m-Y',
                'color' => 'sometimes|string|max:255',
                'gender' => 'sometimes|string|max:255',
                'image' => 'sometimes|image|max:2028',
                'alt_text' => 'sometimes|string|max:255'
            ]);

            // Verificar que el pet pertenece al pet owner autenticado
            $pet = Pet::withTrashed()->where('id', $id)
                ->where('pet_owner_id', Auth::id())
                ->first();

            if (!$pet) {
                return response()->json(['error' => 'Pet not found or you do not have permission to update this pet.'], 404);
            }

            // Convertir la fecha de nacimiento al formato Y-m-d
            if (!empty($validatedData['birth_date'])) {
                $validatedData['birth_date'] = Carbon::createFromFormat('d-m-Y', $validatedData['birth_date'])->format('Y-m-d');
            }

            // Manejo de la carga de imagen
            if ($request->hasFile('image')) {
                // Eliminar la imagen anterior si existe
                if ($pet->photo_id) {
                    $oldImage = Image::find($pet->photo_id);
                    if ($oldImage) {
                        Storage::disk('public')->delete($oldImage->source_url);
                        $oldImage->delete();
                    }
                }

                // Guardar la nueva imagen
                $fileName = time() . '_' . $request->image->getClientOriginalName();
                $filePath = $request->image->storeAs('pet_images', $fileName, 'public');

                $image = new Image();
                $image->source_url = 'storage/' . $filePath;
                $image->alt_text = $request->alt_text;
                $image->save();

                // Asignar el ID de la imagen a la mascota
                $validatedData['photo_id'] = $image->id;
            }

            // Actualizar los datos del pet
            $pet->update($validatedData);

            // Confirmar la transacción
            DB::commit();

            // Devolver la respuesta JSON con la mascota actualizada
            return response()->json($pet);
        } catch (\Exception $e) {
            // Revertir la transacción
            DB::rollBack();

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
            // Buscar la mascota con SoftDeletes
            $pet = Pet::withTrashed()->findOrFail($pet_id);
    
            // Si la mascota tiene una imagen asociada, eliminarla
            if ($pet->photo_id) {
                $image = Image::find($pet->photo_id);
                if ($image) {
                    // Eliminar el archivo de imagen del almacenamiento
                    Storage::disk('public')->delete($image->source_url);
                    // Eliminar el registro de la imagen
                    $image->delete();
                }
            }
    
            // Borrar permanentemente la mascota
            $pet->forceDelete();
    
            return response()->json(['message' => 'Pet permanently deleted']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error deleting pet permanently: ' . $e->getMessage()], 500);
        }
    }
    
}
