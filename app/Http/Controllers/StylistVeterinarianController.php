<?php

namespace App\Http\Controllers;

use App\Models\StylistVeterinarian;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class StylistVeterinarianController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:business_owner_api');
    }

    public function index()
    {
        try {
            $businessOwner = Auth::guard('business_owner_api')->user();
            $businesses = Business::where('business_owner_id', $businessOwner->id)->pluck('id');
            $stylists = StylistVeterinarian::whereIn('business_id', $businesses)->get();

            return response()->json([
                'status' => 'success',
                'stylists' => $stylists,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching stylists',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'phone_number' => ['required', 'string', 'regex:/^[0-9]{9,15}$/'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:stylists_veterinarians'],
                'password' => ['required', 'string', 'min:8'],
                'profile_photo' => ['nullable', 'image', 'max:2048'],
                'business_id' => ['required', 'exists:businesses,id'],
            ]);

            $profilePhotoPath = null;
            if ($request->hasFile('profile_photo')) {
                $profilePhotoPath = $request->file('profile_photo')->store('profile_photos', 'public');
            }

            $stylist = StylistVeterinarian::create([
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
                'message' => 'Stylist created successfully',
                'stylist' => $stylist,
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
                'message' => 'An error occurred while creating the stylist',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $businessOwner = Auth::guard('business_owner_api')->user();
            $businesses = Business::where('business_owner_id', $businessOwner->id)->pluck('id');
            $stylist = StylistVeterinarian::where('id', $id)->whereIn('business_id', $businesses)->firstOrFail();

            return response()->json([
                'status' => 'success',
                'stylist' => $stylist,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching the stylist',
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
                'email' => ['required', 'string', 'email', 'max:255', 'unique:stylists_veterinarians'],
                'password' => [
                    'required', 'string', 'min:8', 'confirmed',
                    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/'
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
    
            $stylist = StylistVeterinarian::create([
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
                'message' => 'Stylist registered successfully',
                'stylist' => $stylist,
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
                'message' => 'An error occurred while registering the stylist',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    

    public function update(Request $request, $id)
    {
        try {
            $businessOwner = Auth::guard('business_owner_api')->user();
            $businesses = Business::where('business_owner_id', $businessOwner->id)->pluck('id');
            $stylist = StylistVeterinarian::where('id', $id)->whereIn('business_id', $businesses)->firstOrFail();

            $request->validate([
                'name' => ['required', 'string', 'regex:/^[a-zA-Z\sáéíóúÁÉÍÓÚüÜñÑ]+$/'],
                'last_name' => ['required', 'string', 'regex:/^[a-zA-Z\sáéíóúÁÉÍÓÚüÜñÑ]+$/'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:stylists_veterinarians'],
                'password' => [
                    'required', 'string', 'min:8', 'confirmed',
                    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/'
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
            

            if ($request->hasFile('profile_photo')) {
                if ($stylist->profile_photo) {
                    Storage::disk('public')->delete($stylist->profile_photo);
                }
                $profilePhotoPath = $request->file('profile_photo')->store('profile_photos', 'public');
                $stylist->profile_photo = $profilePhotoPath;
            }

            if ($request->has('name')) {
                $stylist->name = $request->name;
            }

            if ($request->has('last_name')) {
                $stylist->last_name = $request->last_name;
            }

            if ($request->has('phone_number')) {
                $stylist->phone_number = $request->phone_number;
            }

            if ($request->has('email')) {
                $stylist->email = $request->email;
            }

            if ($request->has('password')) {
                $stylist->password = Hash::make($request->password);
            }

            if ($request->has('business_id')) {
                $stylist->business_id = $request->business_id;
            }

            $stylist->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Stylist updated successfully',
                'stylist' => $stylist,
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
                'message' => 'An error occurred while updating the stylist',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $businessOwner = Auth::guard('business_owner_api')->user();
            $businesses = Business::where('business_owner_id', $businessOwner->id)->pluck('id');
            $stylist = StylistVeterinarian::where('id', $id)->whereIn('business_id', $businesses)->firstOrFail();

            if ($stylist->profile_photo) {
                Storage::disk('public')->delete($stylist->profile_photo);
            }

            $stylist->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Stylist deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while deleting the stylist',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
