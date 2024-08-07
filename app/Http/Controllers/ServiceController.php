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
        $this->middleware('auth:business_owner_api')->except(['index', 'show']);
    }

    public function index(Request $request, $business_id)
    {
        try {
            $perPage = $request->query('per_page', 20);

            if (!is_numeric($perPage) || $perPage <= 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The per_page parameter must be a positive integer.',
                ], 400);
            }

            $services = Service::whereHas('business', function ($query) use ($business_id) {
                $query->where('id', $business_id);
            })->paginate((int)$perPage);

            $services->getCollection()->transform(function ($service) {
                $offer = $service->offer;
                $serviceArray = $service->toArray();

                if ($offer) {
                    $serviceArray = array_merge($serviceArray, $offer->toArray());
                }

                unset($serviceArray['offer']);

                return $serviceArray;
            });

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
                'discount_price' => ['nullable', 'numeric', 'regex:/^\d+(\.\d{1,2})?$/'],
                'offer_start' => ['nullable', 'date'],
                'offer_end' => ['nullable', 'date', 'after_or_equal:offer_start'],
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
                'category' => $request->input('category', 'services'),
                'business_id' => $business->id,
            ]);

            if ($request->has('discount_price') || $request->has('offer_start') || $request->has('offer_end')) {
                $offer = $service->offer()->create([
                    'discount_price' => $request->discount_price,
                    'offer_start' => $request->offer_start,
                    'offer_end' => $request->offer_end,
                ]);
            }

            $serviceArray = $service->toArray();
            if (isset($offer)) {
                $serviceArray = array_merge($serviceArray, $offer->toArray());
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Service created successfully',
                'service' => $serviceArray,
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

            $offer = $service->offer;
            $serviceArray = $service->toArray();

            if ($offer) {
                $serviceArray = array_merge($serviceArray, $offer->toArray());
            }

            unset($serviceArray['offer']);

            return response()->json([
                'status' => 'success',
                'service' => $serviceArray,
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
                'price' => ['sometimes', 'required', 'numeric', 'regex:/^\d+(\.\d{1,2})?$/'],
                'currency' => ['sometimes', 'required', 'string', 'max:3'],
                'max_services_simultaneously' => ['sometimes', 'required', 'integer', 'gt:0'],
                'duration' => ['sometimes', 'required', 'integer', 'gt:1'],
                'category' => ['sometimes', 'string', 'max:45'],
                'discount_price' => ['sometimes', 'nullable', 'numeric', 'regex:/^\d+(\.\d{1,2})?$/'],
                'offer_start' => ['sometimes', 'nullable', 'date'],
                'offer_end' => ['sometimes', 'nullable', 'date', 'after_or_equal:offer_start'],
            ]);

            $service->update($request->only([
                'name',
                'description',
                'price',
                'currency',
                'max_services_simultaneously',
                'duration',
                'category'
            ]));

            $offerData = $request->only(['discount_price', 'offer_start', 'offer_end']);
            if (!empty($offerData)) {
                $offer = $service->offer()->updateOrCreate([], $offerData);
            }

            $serviceArray = $service->toArray();
            if (isset($offer)) {
                $serviceArray = array_merge($serviceArray, $offer->toArray());
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Service updated successfully',
                'service' => $serviceArray,
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
