<?php

namespace App\Http\Controllers;

use App\Models\PetOwner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PetOwnerController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:pet_owner_api', ['except' => ['login', 'register', 'refresh']]);
    }

    public function register(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => ['required', 'string', 'regex:/^[a-zA-Z\s]+$/'],
                'last_name' => ['required', 'string', 'regex:/^[a-zA-Z\s]+$/'],
                'email' => ['required', 'string', 'email', 'unique:pet_owners,email'],
                'password' => [
                    'required', 'string', 'min:8', 'confirmed',
                    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/'
                ],
                'phone_number' => ['required', 'string', 'regex:/^[0-9]{9,15}$/'],
                'profile_photo' => ['nullable', 'image', 'max:2048'],
            ], [
                'name.regex' => 'Name can only contain letters and spaces.',
                'last_name.regex' => 'Last name can only contain letters and spaces.',
                'password.confirmed' => 'The password confirmation does not match.',
                'phone_number.regex' => 'Phone number can only contain numbers and should be between 10 and 15 digits.',
            ]);


            $profilePhotoPath = null;
            if ($request->hasFile('profile_photo')) {
                $profilePhotoPath = $request->file('profile_photo')->store('profile_photos', 'public');
            }

            $petOwner = PetOwner::create([
                'name' => $request->name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone_number' => $request->phone_number,
                'profile_photo' => $profilePhotoPath,
            ]);

            $token = Auth::guard('pet_owner_api')->login($petOwner);
            return response()->json([
                'status' => 'success',
                'message' => 'Pet owner registered successfully',
                'user' => $petOwner,
                'authorisation' => [
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

            \Log::error('Error registering pet owner: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error registering pet owner: ' . $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $credentials = $request->only('email', 'password');

        if (!$token = Auth::guard('pet_owner_api')->attempt($credentials)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        $petOwner = Auth::guard('pet_owner_api')->user();
        return response()->json([
            'status' => 'success',
            'message' => "Welcome {$petOwner->name}!",
            'authorisation' => [
                'token' => $token,
                'type' => 'bearer',
            ]
        ]);
    }


    public function logout()
    {
        Auth::guard('pet_owner_api')->logout();
        return response()->json([
            'status' => 'success',
            'message' => 'Successfully logged out',
        ]);
    }


    public function refresh()
    {
        return response()->json([
            'status' => 'success',
            'user' => Auth::guard('pet_owner_api')->user(),
            'authorisation' => [
                'token' => Auth::guard('pet_owner_api')->refresh(),
                'type' => 'bearer',
            ]
        ]);
    }

    public function show(Request $request)
    {
        $petOwner = Auth::guard('pet_owner_api')->user();
        return response()->json([
            'status' => 'success',
            'user' => $petOwner
        ]);
    }

    public function update(Request $request)
    {
        $petOwner = Auth::guard('pet_owner_api')->user();
    
    
        $validatedData = $request->validate([
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
    }
    
}
