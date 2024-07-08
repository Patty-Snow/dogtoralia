<?php

namespace App\Http\Controllers;

use App\Models\BusinessOwner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class BusinessOwnerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:business_owner_api', ['except' => ['login', 'register', 'refresh', 'trashed', 'restore']]);
    }

    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' => ['required', 'string', 'regex:/^[a-zA-Z\s]+$/'],
                'last_name' => ['required', 'string', 'regex:/^[a-zA-Z\s]+$/'],
                'email' => ['required', 'string', 'email', 'unique:business_owners,email'],
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

            $profilePhotoPath = null;
            if ($request->hasFile('profile_photo')) {
                $profilePhotoPath = $request->file('profile_photo')->store('profile_photos', 'public');
            }

            $businessOwner = BusinessOwner::create([
                'name' => $request->name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone_number' => $request->phone_number,
                'rfc' => $request->rfc,
                'profile_photo' => $profilePhotoPath,
            ]);

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
            $businessOwner = Auth::guard('business_owner_api')->user();

            $request->validate([
                'name' => ['required', 'string', 'regex:/^[a-zA-Z\s]+$/'],
                'last_name' => ['required', 'string', 'regex:/^[a-zA-Z\s]+$/'],
                'email' => ['required', 'string', 'email', 'unique:business_owners,email'],
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

            if ($request->filled('name')) {
                $businessOwner->name = $request->input('name');
            }

            if ($request->filled('last_name')) {
                $businessOwner->last_name = $request->input('last_name');
            }

            if ($request->filled('password')) {
                $businessOwner->password = Hash::make($request->password);
            }

            if ($request->filled('phone_number')) {
                $businessOwner->phone_number = $request->input('phone_number');
            }

            if ($request->hasFile('profile_photo')) {
                if ($businessOwner->profile_photo) {
                    Storage::disk('public')->delete($businessOwner->profile_photo);
                }
                $profilePhotoPath = $request->file('profile_photo')->store('profile_photos', 'public');
                $businessOwner->profile_photo = $profilePhotoPath;
            }

            $businessOwner->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Profile updated successfully',
                'user' => $businessOwner->fresh()
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
            $businessOwner = Auth::guard('business_owner_api')->user();

            if ($businessOwner->profile_photo) {
                Storage::disk('public')->delete($businessOwner->profile_photo);
            }

            $businessOwner->delete();

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
            $businessOwner = BusinessOwner::onlyTrashed()->findOrFail($id);
            $businessOwner->restore();

            return response()->json([
                'status' => 'success',
                'message' => 'Profile restored successfully',
                'user' => $businessOwner
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
            $businessOwner = BusinessOwner::onlyTrashed()->findOrFail($id);

            if ($businessOwner->profile_photo) {
                Storage::disk('public')->delete($businessOwner->profile_photo);
            }

            $businessOwner->forceDelete();

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
            $trashedBusinessOwners = BusinessOwner::onlyTrashed()->get();

            return response()->json([
                'status' => 'success',
                'data' => $trashedBusinessOwners,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while trying to fetch trashed profiles',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
