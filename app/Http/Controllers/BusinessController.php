<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class BusinessController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:business_owner_api')->except(['indexAll', 'show']);;
    }

    public function index(Request $request)
    {
        try {
            $businessOwner = Auth::guard('business_owner_api')->user();

            if (!$businessOwner) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized access. No authenticated business owner found.',
                ], 401);
            }

            // Obtener el número de resultados por página desde los parámetros de consulta o usar 20 por defecto
            $perPage = $request->query('per_page', 20);

            // Validar que perPage sea un número entero positivo
            if (!is_numeric($perPage) || $perPage <= 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The per_page parameter must be a positive integer.',
                ], 400);
            }

            // Obtener los negocios con paginación, incluyendo la relación 'address'
            $businesses = Business::with('address')
                ->where('business_owner_id', $businessOwner->id)
                ->paginate((int)$perPage);
            // Iterar sobre los negocios para agregar la dirección formateada si está disponible
            foreach ($businesses as $business) {
                $business->formatted_address = $business->address ? $business->address->formatted_address : null;

                // Opcionalmente, eliminar la relación cargada para no incluirla en la respuesta final
                unset($business->address);
            }
            return response()->json([
                'status' => 'success',
                'businesses' => $businesses,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching businesses',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function indexAll(Request $request)
    {
        try {
            // Obtener el número de resultados por página desde los parámetros de consulta o usar 20 por defecto
            $perPage = $request->query('per_page', 20);

            // Validar que perPage sea un número entero positivo
            if (!is_numeric($perPage) || $perPage <= 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The per_page parameter must be a positive integer.',
                ], 400);
            }

            // Obtener todos los negocios con paginación, incluyendo la relación 'address'
            $businesses = Business::with('address')->paginate((int)$perPage);

            // Iterar sobre los negocios para agregar la dirección formateada si está disponible
            foreach ($businesses as $business) {
                $business->formatted_address = $business->address ? $business->address->formatted_address : null;

                // Opcionalmente, eliminar la relación cargada para no incluirla en la respuesta final
                unset($business->address);
            }

            return response()->json([
                'status' => 'success',
                'businesses' => $businesses,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching businesses',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'phone_number' => ['required', 'string', 'regex:/^[0-9]{9,15}$/'],
                'email' => ['required', 'string', 'email', 'max:255'],
                'description' => ['nullable', 'string'],
                'profile_photo' => ['nullable', 'image', 'max:2048'],
            ]);

            $profilePhotoPath = null;
            if ($request->hasFile('profile_photo')) {
                $profilePhotoPath = $request->file('profile_photo')->store('profile_photos', 'public');
            }

            $businessOwner = Auth::guard('business_owner_api')->user();

            $business = Business::create([
                'name' => $request->name,
                'phone_number' => $request->phone_number,
                'email' => $request->email,
                'description' => $request->description,
                'profile_photo' => $profilePhotoPath,
                'business_owner_id' => $businessOwner->id,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Business created successfully',
                'business' => $business,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while creating the business',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            // Obtener el negocio por su ID
            $business = Business::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'business' => $business,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching the business',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function update(Request $request, $id)
    {
        try {
            $businessOwner = Auth::guard('business_owner_api')->user();
            $business = Business::where('id', $id)->where('business_owner_id', $businessOwner->id)->firstOrFail();

            // Autorizar la acción de actualización
            $this->authorize('update', $business);

            // Validar la solicitud
            $request->validate([
                'name' => ['sometimes', 'required', 'string', 'max:255'],
                'phone_number' => ['sometimes', 'required', 'string', 'regex:/^[0-9]{9,15}$/'],
                'email' => ['sometimes', 'required', 'string', 'email', 'max:255'],
                'description' => ['nullable', 'string'],
                'profile_photo' => ['nullable', 'image', 'max:2048'],
            ]);

            // Manejar la actualización de la foto de perfil
            if ($request->hasFile('profile_photo')) {
                // Validar la imagen
                $request->validate([
                    'profile_photo' => ['required', 'image', 'max:2048'],
                ]);

                // Eliminar la imagen anterior si existe
                if ($business->profile_photo) {
                    Storage::disk('public')->delete($business->profile_photo);
                }

                // Guardar la nueva imagen
                $profilePhotoPath = $request->file('profile_photo')->store('profile_photos', 'public');
                $business->profile_photo = $profilePhotoPath;
            }

            // Actualizar los campos restantes si están presentes en la solicitud
            if ($request->has('name')) {
                $business->name = $request->get('name');
            }

            if ($request->has('phone_number')) {
                $business->phone_number = $request->get('phone_number');
            }

            if ($request->has('email')) {
                $business->email = $request->get('email');
            }

            if ($request->has('description')) {
                $business->description = $request->get('description');
            }

            // Guardar los cambios
            $business->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Business updated successfully',
                'business' => $business,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while updating the business',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $businessOwner = Auth::guard('business_owner_api')->user();
            $business = Business::where('id', $id)->where('business_owner_id', $businessOwner->id)->firstOrFail();

            if ($business->profile_photo) {
                Storage::disk('public')->delete($business->profile_photo);
            }

            $business->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Business deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while deleting the business',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function restore($id)
    {
        try {
            $businessOwner = Auth::guard('business_owner_api')->user();
            $business = Business::where('id', $id)->where('business_owner_id', $businessOwner->id)->onlyTrashed()->firstOrFail();

            $business->restore();

            return response()->json([
                'status' => 'success',
                'message' => 'Business restored successfully',
                'business' => $business,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while restoring the business',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function forceDelete($id)
    {
        try {
            $businessOwner = Auth::guard('business_owner_api')->user();
            $business = Business::where('id', $id)->where('business_owner_id', $businessOwner->id)->onlyTrashed()->firstOrFail();

            if ($business->profile_photo) {
                Storage::disk('public')->delete($business->profile_photo);
            }

            $business->forceDelete();

            return response()->json([
                'status' => 'success',
                'message' => 'Business permanently deleted',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while permanently deleting the business',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function trashed()
    {
        try {
            $businessOwner = Auth::guard('business_owner_api')->user();
            $trashedBusinesses = Business::where('business_owner_id', $businessOwner->id)->onlyTrashed()->get();

            return response()->json([
                'status' => 'success',
                'data' => $trashedBusinesses,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching trashed businesses',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
