<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin_api', ['except' => ['login']]);
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => ['required', 'string', 'email'],
                'password' => ['required', 'string', 'min:8'],
            ]);
    
            $credentials = $request->only('email', 'password');
    
            if (!$token = Auth::guard('admin_api')->attempt($credentials)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid credentials',
                ], 401);
            }
    
            $admin = Auth::guard('admin_api')->user();
            return response()->json([
                'status' => 'success',
                'message' => "Welcome {$admin->name}",
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
            Auth::guard('admin_api')->logout();
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

    public function show()
    {
        try {
            $admin = Auth::guard('admin_api')->user();

            return response()->json([
                'status' => 'success',
                'user' => $admin
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
    