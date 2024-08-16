<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BusinessOwner;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\UnauthorizedException;

class BusinessOwnerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:business_owner_api', ['except' => ['login', 'register', 'refresh', 'trashed', 'restore']]);
    }

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
                'rfc' => ['required', 'string', 'max:13'],
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

            // Crear el nuevo BusinessOwner
            $businessOwner = BusinessOwner::create([
                'name' => $request->name,
                'last_name' => $request->last_name,
                'email' => $email,
                'password' => Hash::make($request->password),
                'phone_number' => $request->phone_number,
                'rfc' => $request->rfc,
                'profile_photo' => $profilePhotoPath,
            ]);

            // Iniciar sesión al nuevo usuario
            $token = Auth::guard('business_owner_api')->login($businessOwner);
            return response()->json([
                'status' => 'success',
                'message' => 'Business owner registered successfully',
                'user' => $businessOwner,
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
                'message' => 'Error registering business owner: ' . $e->getMessage()
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

            if (!$token = Auth::guard('business_owner_api')->attempt($credentials)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid credentials',
                ], 401);
            }

            $businessOwner = Auth::guard('business_owner_api')->user();
            return response()->json([
                'status' => 'success',
                'message' => "Welcome {$businessOwner->name}!",
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
            Auth::guard('business_owner_api')->logout();
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
                'user' => Auth::guard('business_owner_api')->user(),
                'authorization' => [
                    'token' => Auth::guard('business_owner_api')->refresh(),
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

 
    public function show()
    {
        try {
            $businessOwner = Auth::guard('business_owner_api')->user();

            return response()->json([
                'status' => 'success',
                'user' => $businessOwner
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
            $admin = Auth::guard('admin_api')->user();
    
            $request->validate([
                'name' => ['sometimes', 'string', 'regex:/^[a-zA-Z\s]+$/'],
                'email' => ['sometimes', 'string', 'email', 'unique:admins,email,' . $admin->id],
                'password' => [
                    'sometimes', 'string', 'min:8', 'confirmed',
                    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};\'":\\|,.<>\/?])[A-Za-z\d!@#$%^&*()_+\-=\[\]{};\'":\\|,.<>\/?]{8,}$/'
                ],
            ], [
                'name.regex' => 'Name can only contain letters and spaces.',
                'password.confirmed' => 'The password confirmation does not match.',
            ]);
    
            if ($request->filled('name')) {
                $admin->name = $request->input('name');
            }
    
            if ($request->filled('email')) {
                $admin->email = $request->input('email');
            }
    
            if ($request->filled('password')) {
                $admin->password = Hash::make($request->password);
            }
    
            $admin->save();
    
            return response()->json([
                'status' => 'success',
                'message' => 'Profile updated successfully',
                'user' => $admin->fresh()
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
}