<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class OfferController extends Controller
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

            $offers = Offer::whereHas('service.business', function ($query) use ($businessOwner) {
                $query->where('business_owner_id', $businessOwner->id);
            })->paginate((int)$perPage);

            return response()->json([
                'status' => 'success',
                'offers' => $offers,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching offers',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'service_id' => ['required', 'exists:services,id'],
                'discount_price' => ['required', 'numeric', 'regex:/^\d+(\.\d{1,2})?$/'],
                'start_date' => ['required', 'date'],
                'end_date' => ['required', 'date', 'after:start_date'],
                'description' => ['required', 'string'],
            ]);

            $businessOwner = Auth::guard('business_owner_api')->user();
            $service = Service::where('id', $request->service_id)
                ->whereHas('business', function ($query) use ($businessOwner) {
                    $query->where('business_owner_id', $businessOwner->id);
                })->firstOrFail();

            $offer = Offer::create([
                'service_id' => $service->id,
                'discount_price' => $request->discount_price,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'description' => $request->description,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Offer created successfully',
                'offer' => $offer,
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
                'message' => 'An error occurred while creating the offer',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $offer = Offer::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'offer' => $offer,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching the offer',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $businessOwner = Auth::guard('business_owner_api')->user();
            $offer = Offer::where('id', $id)->whereHas('service.business', function ($query) use ($businessOwner) {
                $query->where('business_owner_id', $businessOwner->id);
            })->firstOrFail();

            $request->validate([
                'discount_price' => ['sometimes', 'required', 'numeric', 'regex:/^\d+(\.\d{1,2})?$/'],
                'start_date' => ['sometimes', 'required', 'date'],
                'end_date' => ['sometimes', 'required', 'date', 'after:start_date'],
                'description' => ['sometimes', 'required', 'string'],
            ]);

            if ($request->has('discount_price')) {
                $offer->discount_price = $request->get('discount_price');
            }

            if ($request->has('start_date')) {
                $offer->start_date = $request->get('start_date');
            }

            if ($request->has('end_date')) {
                $offer->end_date = $request->get('end_date');
            }

            if ($request->has('description')) {
                $offer->description = $request->get('description');
            }

            $offer->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Offer updated successfully',
                'offer' => $offer,
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
                'message' => 'An error occurred while updating the offer',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $businessOwner = Auth::guard('business_owner_api')->user();
            $offer = Offer::where('id', $id)->whereHas('service.business', function ($query) use ($businessOwner) {
                $query->where('business_owner_id', $businessOwner->id);
            })->firstOrFail();

            $offer->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Offer deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while deleting the offer',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
