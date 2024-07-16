<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ServiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:business_owner_api')->except(['indexAll', 'show']);
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

            $perPage = $request->query('per_page', 20);

            if (!is_numeric($perPage) || $perPage <= 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The per_page parameter must be a positive integer.',
                ], 400);
            }

            $services = Service::whereHas('business', function ($query) use ($businessOwner) {
                $query->where('business_owner_id', $businessOwner->id);
            })->with('offer')->paginate((int)$perPage);

            return response()->json([
                'status' => 'success',
                'services' => $services,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching services',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:45'],
                'description' => ['required', 'string'],
                'price' => ['required', 'numeric', 'regex:/^\d+(\.\d{1,2})?$/'],
                'currency' => ['required', 'string', 'max:3'],
                'max_services_simultaneously' => ['required', 'integer', 'gt:0'],
                'duration' => ['required', 'integer', 'gt:1'],
                'business_id' => ['required', 'exists:businesses,id'],
            ]);

            $businessOwner = Auth::guard('business_owner_api')->user();
            $business = Business::where('id', $request->business_id)
                ->where('business_owner_id', $businessOwner->id)
                ->firstOrFail();

            $service = Service::create([
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'currency' => $request->currency,
                'max_services_simultaneously' => $request->max_services_simultaneously,
                'duration' => $request->duration,
                'business_id' => $business->id,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Service created successfully',
                'service' => $service,
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
                'message' => 'An error occurred while creating the service',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $service = Service::with('offer')->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'service' => $service,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching the service',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $businessOwner = Auth::guard('business_owner_api')->user();
            $service = Service::where('id', $id)->whereHas('business', function ($query) use ($businessOwner) {
                $query->where('business_owner_id', $businessOwner->id);
            })->firstOrFail();

            $request->validate([
                'name' => ['sometimes', 'required', 'string', 'max:45'],
                'description' => ['sometimes', 'required', 'string'],
                'price' => ['sometimes', 'required', 'numeric'],
                'currency' => ['sometimes', 'required', 'string', 'max:3'],
                'max_services_simultaneously' => ['sometimes', 'required', 'integer', 'gt:0'],
                'duration' => ['sometimes', 'required', 'integer', 'gt:1'],
            ]);

            if ($request->has('name')) {
                $service->name = $request->get('name');
            }

            if ($request->has('description')) {
                $service->description = $request->get('description');
            }

            if ($request->has('price')) {
                $service->price = $request->get('price');
            }

            if ($request->has('currency')) {
                $service->currency = $request->get('currency');
            }

            if ($request->has('max_services_simultaneously')) {
                $service->max_services_simultaneously = $request->get('max_services_simultaneously');
            }

            if ($request->has('duration')) {
                $service->duration = $request->get('duration');
            }

            $service->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Service updated successfully',
                'service' => $service,
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
                'message' => 'An error occurred while updating the service',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $businessOwner = Auth::guard('business_owner_api')->user();
            $service = Service::where('id', $id)->whereHas('business', function ($query) use ($businessOwner) {
                $query->where('business_owner_id', $businessOwner->id);
            })->firstOrFail();

            $service->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Service deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while deleting the service',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
