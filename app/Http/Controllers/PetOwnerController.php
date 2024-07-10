<?php

namespace App\Http\Controllers;

use App\Models\PetOwner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Support\Facades\Log;

class PetOwnerController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('auth:pet_owner_api', ['except' => ['login', 'register', 'refresh', 'trashed', 'restore', 'index', 'show']]);
    // }

    public function register(Request $request)
    {
        try {
            // Validar la entrada básica
            $request->validate([
                'name' => ['required', 'string', 'regex:/^[a-zA-Z\s]+$/'],
                'last_name' => ['required', 'string', 'regex:/^[a-zA-Z\s]+$/'],
                'password' => [
                    'required', 'string', 'min:8', 'confirmed',
                    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};\'":\\|,.<>\/?])[A-Za-z\d!@#$%^&*()_+\-=\[\]{};\'":\\|,.<>\/?]{8,}$/'
                ],
                'phone_number' => ['required', 'string', 'regex:/^[0-9]{9,15}$/'],
                'profile_photo' => ['nullable', 'image', 'max:2048'],
            ], [
                'name.regex' => 'Name can only contain letters and spaces.',
                'last_name.regex' => 'Last name can only contain letters and spaces.',
                'password.confirmed' => 'The password confirmation does not match.',
                'phone_number.regex' => 'Phone number can only contain numbers and should be between 9 and 15 digits.',
            ]);

            // Validar que el correo electrónico sea único en las tres tablas
            $email = $request->input('email');
            $emailExistsInBusinessOwners = \App\Models\BusinessOwner::where('email', $email)->exists();
            $emailExistsInPetOwners = \App\Models\PetOwner::where('email', $email)->exists();
            $emailExistsInStaffs = \App\Models\Staff::where('email', $email)->exists();

            if ($emailExistsInBusinessOwners || $emailExistsInPetOwners || $emailExistsInStaffs) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The email address is already taken.'
                ], 422);
            }

            // Manejar la carga de la foto de perfil
            $profilePhotoPath = null;
            if ($request->hasFile('profile_photo')) {
                $profilePhotoPath = $request->file('profile_photo')->store('profile_photos', 'public');
            }

            // Crear el nuevo PetOwner
            $petOwner = PetOwner::create([
                'name' => $request->name,
                'last_name' => $request->last_name,
                'email' => $email,
                'password' => Hash::make($request->password),
                'phone_number' => $request->phone_number,
                'profile_photo' => $profilePhotoPath,
            ]);

            // Iniciar sesión al nuevo usuario
            $token = Auth::guard('pet_owner_api')->login($petOwner);
            return response()->json([
                'status' => 'success',
                'message' => 'Pet owner registered successfully',
                'user' => $petOwner,
                'authorization' => [
                    'token' => $token,
                    'type' => 'bearer',
                ]
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error: ' . $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error registering pet owner: ' . $e->getMessage()
            ], 500);
        }
    }


    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => ['required', 'string', 'email'],
                'password' => ['required', 'string', 'min:8'],
            ]);

            $credentials = $request->only('email', 'password');

            if (!$token = Auth::guard('pet_owner_api')->attempt($credentials)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid credentials',
                ], 401);
            }

            $petOwner = Auth::guard('pet_owner_api')->user();
            return response()->json([
                'status' => 'success',
                'message' => "Welcome {$petOwner->name}!",
                'authorization' => [
                    'token' => $token,
                    'type' => 'bearer',
                ]
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
                'message' => 'An error occurred while trying to login',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function logout()
    {
        try {
            Auth::guard('pet_owner_api')->logout();
            return response()->json([
                'status' => 'success',
                'message' => 'Successfully logged out',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while trying to logout',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function refresh()
    {
        try {
            return response()->json([
                'status' => 'success',
                'user' => Auth::guard('pet_owner_api')->user(),
                'authorization' => [
                    'token' => Auth::guard('pet_owner_api')->refresh(),
                    'type' => 'bearer',
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while trying to refresh the token',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($pet_owner_id)
    {
        try {
            // Verificar si el usuario autenticado es el pet owner con el ID proporcionado
            if (Auth::guard('pet_owner_api')->check()) {
                if (Auth::id() != $pet_owner_id) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Unauthorized access.',
                    ], 401);
                }
                $petOwner = Auth::guard('pet_owner_api')->user();
            }
            // Verificar si el usuario autenticado es un business owner o staff
            elseif (Auth::guard('business_owner_api')->check() || Auth::guard('staff_api')->check()) {
                $petOwner = PetOwner::findOrFail($pet_owner_id);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized access.',
                ], 401);
            }

            return response()->json([
                'status' => 'success',
                'user' => $petOwner
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while trying to fetch the user data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function update(Request $request)
    {
        try {
            $petOwner = Auth::guard('pet_owner_api')->user();

            $request->validate([
                'name' => ['sometimes', 'string', 'regex:/^[a-zA-Z\s]+$/'],
                'last_name' => ['sometimes', 'string', 'regex:/^[a-zA-Z\s]+$/'],
                'password' => [
                    'sometimes', 'string', 'min:8', 'confirmed',
                    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/'
                ],
                'phone_number' => ['sometimes', 'string', 'regex:/^[0-9]{9,15}$/'],
                'profile_photo' => ['nullable', 'image', 'max:2048'],
            ], [
                'name.regex' => 'Name can only contain letters and spaces.',
                'last_name.regex' => 'Last name can only contain letters and spaces.',
                'password.confirmed' => 'The password confirmation does not match.',
                'phone_number.regex' => 'Phone number can only contain numbers and should be between 10 and 15 digits.',
            ]);

            if ($request->filled('name')) {
                $petOwner->name = $request->input('name');
            }

            if ($request->filled('last_name')) {
                $petOwner->last_name = $request->input('last_name');
            }

            if ($request->filled('password')) {
                $petOwner->password = Hash::make($request->password);
            }

            if ($request->filled('phone_number')) {
                $petOwner->phone_number = $request->input('phone_number');
            }

            if ($request->hasFile('profile_photo')) {
                if ($petOwner->profile_photo) {
                    Storage::disk('public')->delete($petOwner->profile_photo);
                }
                $profilePhotoPath = $request->file('profile_photo')->store('profile_photos', 'public');
                $petOwner->profile_photo = $profilePhotoPath;
            }

            $petOwner->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Profile updated successfully',
                'user' => $petOwner->fresh()
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
                'message' => 'An error occurred while trying to update the profile',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy()
    {
        try {
            $petOwner = Auth::guard('pet_owner_api')->user();

            if ($petOwner->profile_photo) {
                Storage::disk('public')->delete($petOwner->profile_photo);
            }

            $petOwner->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Profile deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while trying to delete the profile',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function restore($id)
    {
        try {
            $petOwner = PetOwner::onlyTrashed()->findOrFail($id);
            $petOwner->restore();

            return response()->json([
                'status' => 'success',
                'message' => 'Profile restored successfully',
                'user' => $petOwner
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while trying to restore the profile',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function forceDelete($id)
    {
        try {
            $petOwner = PetOwner::onlyTrashed()->findOrFail($id);

            if ($petOwner->profile_photo) {
                Storage::disk('public')->delete($petOwner->profile_photo);
            }

            $petOwner->forceDelete();

            return response()->json([
                'status' => 'success',
                'message' => 'Profile permanently deleted',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while trying to permanently delete the profile',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function trashed()
    {
        try {
            $trashedPetOwners = PetOwner::onlyTrashed()->get();

            return response()->json([
                'status' => 'success',
                'data' => $trashedPetOwners,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while trying to fetch trashed profiles',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function index(Request $request)
    {
        try {
            // Verificar si el usuario autenticado es un business owner o staff
            if (Auth::guard('business_owner_api')->check() || Auth::guard('staff_api')->check()) {
                // Definir el número de resultados por página con un valor predeterminado de 20
                $perPage = $request->query('per_page', 20);

                // Obtener todos los PetOwners, incluidos los eliminados
                $petOwners = PetOwner::withTrashed()->paginate($perPage);

                // Log para depuración
                \Log::info('Pet Owners Count: ' . $petOwners->count());

                if ($petOwners->isEmpty()) {
                    return response()->json([
                        'status' => 'success',
                        'message' => 'No pet owners found.',
                        'pet_owners' => $petOwners,
                    ]);
                }

                return response()->json([
                    'status' => 'success',
                    'pet_owners' => $petOwners,
                ]);
            } else {
                // Otros casos, lanzar excepción de no autorizado
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized access.',
                ], 401);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error fetching pet owners: ' . $e->getMessage()], 500);
        }
    }
}