<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class BusinessScheduleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:business_owner_api')->except(['show']);
    }

    public function show($businessId)
    {
        try {
            $businessOwner = Auth::guard('business_owner_api')->user();
            $business = Business::where('id', $businessId)->where('business_owner_id', $businessOwner->id)->firstOrFail();

            $schedule = BusinessSchedule::where('business_id', $businessId)->get();

            return response()->json([
                'status' => 'success',
                'schedule' => $schedule,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching the business schedule',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'business_id' => ['required', 'exists:businesses,id'],
                'schedule' => ['required', 'array'],
                'schedule.*.day_of_week' => ['required', 'string'],
                'schedule.*.start_time' => ['required', 'string', 'date_format:H:i'],
                'schedule.*.end_time' => ['required', 'string', 'date_format:H:i'],
            ]);

            $businessOwner = Auth::guard('business_owner_api')->user();
            $business = Business::where('id', $request->business_id)->where('business_owner_id', $businessOwner->id)->firstOrFail();

            BusinessSchedule::where('business_id', $request->business_id)->delete();

            foreach ($request->schedule as $item) {
                BusinessSchedule::create([
                    'business_id' => $request->business_id,
                    'day_of_week' => $item['day_of_week'],
                    'start_time' => $item['start_time'],
                    'end_time' => $item['end_time'],
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Schedule created successfully',
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
                'message' => 'An error occurred while creating the schedule',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $businessId)
    {
        try {
            $request->validate([
                'schedule' => ['required', 'array'],
                'schedule.*.day_of_week' => ['required', 'string'],
                'schedule.*.start_time' => ['required', 'string', 'date_format:H:i'],
                'schedule.*.end_time' => ['required', 'string', 'date_format:H:i'],
            ]);

            $businessOwner = Auth::guard('business_owner_api')->user();
            $business = Business::where('id', $businessId)->where('business_owner_id', $businessOwner->id)->firstOrFail();

            BusinessSchedule::where('business_id', $businessId)->delete();

            foreach ($request->schedule as $item) {
                BusinessSchedule::create([
                    'business_id' => $businessId,
                    'day_of_week' => $item['day_of_week'],
                    'start_time' => $item['start_time'],
                    'end_time' => $item['end_time'],
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Schedule updated successfully',
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
                'message' => 'An error occurred while updating the schedule',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($businessId)
    {
        try {
            $businessOwner = Auth::guard('business_owner_api')->user();
            $business = Business::where('id', $businessId)->where('business_owner_id', $businessOwner->id)->firstOrFail();

            BusinessSchedule::where('business_id', $businessId)->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Schedule deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while deleting the schedule',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
