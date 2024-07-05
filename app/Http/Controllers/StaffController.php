<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class StaffController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:business_owner_api')->except(['login']);
    }

    public function index()
    {
        try {
            $businessOwner = Auth::guard('business_owner_api')->user();
            $businesses = Business::where('business_owner_id', $businessOwner->id)->pluck('id');
            $staff = Staff::whereIn('business_id', $businesses)->get();

            return response()->json([
                'status' => 'success',
                'stylists' => $staff,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching stylists',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function show($id)
    {
        try {
            $businessOwner = Auth::guard('business_owner_api')->user();
            $businesses = Business::where('business_owner_id', $businessOwner->id)->pluck('id');
            $staff = Staff::where('id', $id)->whereIn('business_id', $businesses)->firstOrFail();

            return response()->json([
                'status' => 'success',
                'staff' => $staff,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching the staff',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' => ['required', 'string', 'regex:/^[a-zA-Z\sáéíóúÁÉÍÓÚüÜñÑ]+$/'],
                'last_name' => ['required', 'string', 'regex:/^[a-zA-Z\sáéíóúÁÉÍÓÚüÜñÑ]+$/'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:staffs'],
                'password' => [
                    'required', 'string', 'min:8', 'confirmed',
                    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/'
                    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};\'":\\|,.<>\/?])[A-Za-z\d!@#$%^&*()_+\-=\[\]{};\'":\\|,.<>\/?]{8,}$/'
                ],
                'phone_number' => ['required', 'string', 'regex:/^[0-9]{9,15}$/'],
                'profile_photo' => ['nullable', 'image', 'max:2048'],
                'business_id' => ['required', 'exists:businesses,id'],
            ], [
                'name.regex' => 'The name can only contain letters and spaces, including letters with accents.',
                'last_name.regex' => 'The last name can only contain letters and spaces, including letters with accents.',
                'email.unique' => 'The email has already been taken.',
                'password.confirmed' => 'The password confirmation does not match.',
                'phone_number.regex' => 'The phone number can only contain numbers and should be between 9 and 15 digits.',
                'business_id.exists' => 'Invalid business ID.',
            ]);

            $profilePhotoPath = null;
            if ($request->hasFile('profile_photo')) {
                $profilePhotoPath = $request->file('profile_photo')->store('profile_photos', 'public');
            }

            $staff = Staff::create([
                'name' => $request->name,
                'last_name' => $request->last_name,
                'phone_number' => $request->phone_number,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'profile_photo' => $profilePhotoPath,
                'business_id' => $request->business_id,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Staff registered successfully',
                'staff' => $staff,
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
                'message' => 'An error occurred while registering the staff',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = Auth::guard('staff_api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer'
        ]);
    }

    public function logout()
    {
        try {
            Auth::guard('staff_api')->logout();

            return response()->json([
                'status' => 'success',
                'message' => 'Successfully logged out',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while logging out',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    public function update(Request $request, $id)
    {
        try {
            $businessOwner = Auth::guard('business_owner_api')->user();
            $businesses = Business::where('business_owner_id', $businessOwner->id)->pluck('id');
            $staff = Staff::where('id', $id)->whereIn('business_id', $businesses)->firstOrFail();

            $request->validate([
                'name' => ['sometimes', 'string', 'regex:/^[a-zA-Z\sáéíóúÁÉÍÓÚüÜñÑ]+$/'],
                'last_name' => ['sometimes', 'string', 'regex:/^[a-zA-Z\sáéíóúÁÉÍÓÚüÜñÑ]+$/'],
                'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:staffs'],
                'password' => [
                    'sometimes', 'string', 'min:8', 'confirmed',
                    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};\'":\\|,.<>\/?])[A-Za-z\d!@#$%^&*()_+\-=\[\]{};\'":\\|,.<>\/?]{8,}$/'
                ],
                'phone_number' => ['sometimes', 'string', 'regex:/^[0-9]{9,15}$/'],
                'profile_photo' => ['nullable', 'image', 'max:2048'],
                'business_id' => ['sometimes', 'exists:businesses,id'],
            ], [
                'name.regex' => 'The name can only contain letters and spaces, including letters with accents.',
                'last_name.regex' => 'The last name can only contain letters and spaces, including letters with accents.',
                'email.unique' => 'The email has already been taken.',
                'password.confirmed' => 'The password confirmation does not match.',
                'phone_number.regex' => 'The phone number can only contain numbers and should be between 9 and 15 digits.',
                'business_id.exists' => 'Invalid business ID.',
            ]);


            if ($request->hasFile('profile_photo')) {
                if ($staff->profile_photo) {
                    Storage::disk('public')->delete($staff->profile_photo);
                }
                $profilePhotoPath = $request->file('profile_photo')->store('profile_photos', 'public');
                $staff->profile_photo = $profilePhotoPath;
            }

            if ($request->has('name')) {
                $staff->name = $request->name;
            }

            if ($request->has('last_name')) {
                $staff->last_name = $request->last_name;
            }

            if ($request->has('phone_number')) {
                $staff->phone_number = $request->phone_number;
            }

            if ($request->has('email')) {
                $staff->email = $request->email;
            }

            if ($request->has('password')) {
                $staff->password = Hash::make($request->password);
            }

            if ($request->has('business_id')) {
                $staff->business_id = $request->business_id;
            }

            $staff->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Staff updated successfully',
                'staff' => $staff,
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
                'message' => 'An error occurred while updating the staff',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $businessOwner = Auth::guard('business_owner_api')->user();
            $businesses = Business::where('business_owner_id', $businessOwner->id)->pluck('id');
            $staff = Staff::where('id', $id)->whereIn('business_id', $businesses)->firstOrFail();

            if ($staff->profile_photo) {
                Storage::disk('public')->delete($staff->profile_photo);
            }

            $staff->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Staff deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while deleting the staff',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function trashed()
    {
        try {
            $businessOwner = Auth::guard('business_owner_api')->user();
            $businesses = Business::where('business_owner_id', $businessOwner->id)->pluck('id');
            $trashedStaff = Staff::whereIn('business_id', $businesses)->onlyTrashed()->get();

            return response()->json([
                'status' => 'success',
                'data' => $trashedStaff,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching trashed staff',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function restore($id)
    {
        try {
            $businessOwner = Auth::guard('business_owner_api')->user();
            $businesses = Business::where('business_owner_id', $businessOwner->id)->pluck('id');
            $staff = Staff::where('id', $id)->whereIn('business_id', $businesses)->onlyTrashed()->firstOrFail();

            $staff->restore();

            return response()->json([
                'status' => 'success',
                'message' => 'Staff restored successfully',
                'staff' => $staff,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while restoring the staff',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function forceDelete($id)
    {
        try {
            $businessOwner = Auth::guard('business_owner_api')->user();
            $businesses = Business::where('business_owner_id', $businessOwner->id)->pluck('id');
            $staff = Staff::where('id', $id)->whereIn('business_id', $businesses)->onlyTrashed()->firstOrFail();

            if ($staff->profile_photo) {
                Storage::disk('public')->delete($staff->profile_photo);
            }

            $staff->forceDelete();

            return response()->json([
                'status' => 'success',
                'message' => 'Staff permanently deleted',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while permanently deleting the staff',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
