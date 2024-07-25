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
            $business = Business::find($businessId);

            if (!$business) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The business does not exist.',
                ], 404); // 404 Not Found
            }

            $schedules = BusinessSchedule::where('business_id', $businessId)->get();

            $formattedSchedules = $schedules->map(function ($schedule) {
                $formattedTimeSlots = array_map(function ($timeSlot) {
                    return [
                        'start_time' => $timeSlot['start_time'],
                        'end_time' => $timeSlot['end_time'],
                    ];
                }, $schedule->time_slots);

                return [
                    'id' => $schedule->id,
                    'business_id' => $schedule->business_id,
                    'day_of_week' => $schedule->day_of_week,
                    'time_slots' => $formattedTimeSlots,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'created_at' => $schedule->created_at,
                    'updated_at' => $schedule->updated_at,
                ];
            });

            return response()->json([
                'status' => 'success',
                'schedule' => $formattedSchedules,
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
                'schedule.*.time_slots' => ['required', 'array'],
                'schedule.*.time_slots.*.start_time' => ['required', 'string', 'date_format:H:i'],
                'schedule.*.time_slots.*.end_time' => ['required', 'string', 'date_format:H:i'],
            ]);

            $businessOwner = Auth::guard('business_owner_api')->user();
            $business = Business::where('id', $request->business_id)
                ->where('business_owner_id', $businessOwner->id)
                ->firstOrFail();


            $createdSchedules = [];

            foreach ($request->schedule as $item) {
                $timeSlots = $item['time_slots'];

                // Obtener la primera y última franja horaria
                $firstSlot = $timeSlots[0];
                $lastSlot = end($timeSlots);

                $schedule = BusinessSchedule::create([
                    'business_id' => $request->business_id,
                    'day_of_week' => $item['day_of_week'],
                    'start_time' => $firstSlot['start_time'],
                    'end_time' => $lastSlot['end_time'],
                    'time_slots' => $timeSlots,
                ]);

                $createdSchedules[] = $schedule;
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Schedule created successfully',
                'data' => $createdSchedules,
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




    public function update(Request $request, $schedule_id)
    {
        try {
            $request->validate([
                'schedule' => ['required', 'array'],
                'schedule.*.time_slots' => ['required', 'array'],
                'schedule.*.time_slots.*.start_time' => ['required', 'string', 'date_format:H:i'],
                'schedule.*.time_slots.*.end_time' => ['required', 'string', 'date_format:H:i'],
            ]);

            $businessOwner = Auth::guard('business_owner_api')->user();

            // Obtener el horario especificado
            $schedule = BusinessSchedule::where('id', $schedule_id)
                ->whereHas('business', function ($query) use ($businessOwner) {
                    $query->where('business_owner_id', $businessOwner->id);
                })
                ->first();

            if (!$schedule) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The schedule does not belong to the authenticated business owner.',
                ], 403); // 403 Forbidden
            }

            $timeSlots = $request->schedule[0]['time_slots'];

            // Obtener la primera y última franja horaria
            $firstSlot = $timeSlots[0];
            $lastSlot = end($timeSlots);

            $schedule->update([
                'start_time' => $firstSlot['start_time'],
                'end_time' => $lastSlot['end_time'],
                'time_slots' => $timeSlots,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Schedule updated successfully',
                'data' => $schedule,
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
