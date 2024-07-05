<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Staff;
use App\Models\Business;
use Illuminate\Http\Request;
use App\Models\Staff\StaffSchedule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Staff\StaffScheduleDay;
use App\Models\Staff\StaffScheduleHour;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class StaffScheduleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:staff_api');
    }

    public function index(Request $request)
    {
        $staff = Auth::guard('staff_api')->user();

        if (!$staff) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        $schedules = StaffSchedule::with(['day', 'hour'])
            ->where('staff_id', $staff->id)
            ->get()
            ->groupBy('day.day');

        $grouped_schedule = [];

        foreach ($schedules as $day => $schedule) {
            $hours = $schedule->map(function ($schedule) {
                return $schedule->hour->hour_start;
            });

            $grouped_schedule[] = [
                'day' => $day,
                'hours' => $hours
            ];
        }

        return response()->json([
            'message' => 'Schedule for ' . $staff->name,
            'schedule' => $grouped_schedule,
        ]);
    }

    public function config()
    {
        $hours_days = collect([]);

        $staff_schedule_hours = StaffScheduleHour::all();
        foreach ($staff_schedule_hours->groupBy("hour") as $key => $schedule_hour) {
            $hours_days->push([
                'hour' => $key,
                'format_hour' => Carbon::parse(date("Y-m-d") . ' ' . $key . ":00:00")->format("h:i A"),
                'items' => $schedule_hour->map(function ($hour_item) {
                    return [
                        'id' => $hour_item->id,
                        'hour_start' => $hour_item->hour_start,
                        'hour_end' => $hour_item->hour_end,
                        'format_hour_start' => Carbon::parse(date("Y-m-d") . ' ' . $hour_item->hour_start)->format("h:i A"),
                        'format_hour_end' => Carbon::parse(date("Y-m-d") . ' ' . $hour_item->hour_end)->format("h:i A"),
                        'hour' => $hour_item->hour,
                    ];
                }),
            ]);
        }

        return response()->json([
            'hours_days' => $hours_days,
        ]);
    }

    public function store(Request $request)
    {
        $staff = Auth::guard('staff_api')->user();
    
        if (!$staff) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }
    
        $schedule_hours = $request->input('schedule_hours');
        $assigned_schedule = [];
        $taken_hours = [];
        $invalid_days = [];
        $invalid_hours = [];
    
        // Validar que los días existen
        foreach ($schedule_hours as $schedule_hour) {
            $day = StaffScheduleDay::where('day', $schedule_hour["day_name"])->first();
            if (!$day) {
                $invalid_days[] = $schedule_hour["day_name"];
            }
        }
    
        if (!empty($invalid_days)) {
            return response()->json([
                'message' => 'The following days do not exist: ' . implode(', ', $invalid_days),
            ], 422);
        }
    
        // Validar que las horas existen
        foreach ($schedule_hours as $schedule_hour) {
            foreach ($schedule_hour["children"] as $children) {
                $hour = StaffScheduleHour::find($children["item"]["id"]);
                if (!$hour) {
                    $invalid_hours[] = $children["item"]["hour_start"] ?? 'Hour ID ' . $children["item"]["id"];
                }
            }
        }
    
        if (!empty($invalid_hours)) {
            return response()->json([
                'message' => 'The hours selected do not exist: ' . implode(', ', $invalid_hours),
            ], 422);
        }
    
        // ALMACENAR LA DISPONIBILIDAD DE HORARIO DEL STAFF
        foreach ($schedule_hours as $schedule_hour) {
            if (sizeof($schedule_hour["children"]) > 0) {
                $day = StaffScheduleDay::where('day', $schedule_hour["day_name"])->first();
                foreach ($schedule_hour["children"] as $children) {
                    $existingSchedule = StaffSchedule::where('staff_id', $staff->id)
                        ->where('staff_schedule_day_id', $day->id)
                        ->where('staff_schedule_hour_id', $children["item"]["id"])
                        ->first();
    
                    if ($existingSchedule) {
                        $taken_hours[$schedule_hour["day_name"]][] = StaffScheduleHour::find($children["item"]["id"])->hour_start;
                    }
                }
            }
        }
    
        if (!empty($taken_hours)) {
            $messages = [];
            foreach ($taken_hours as $day => $hours) {
                $messages[] = [
                    'day' => $day,
                    'hours' => $hours,
                ];
            }
            return response()->json([
                'message' => 'The following hours are already assigned for the selected days:',
                'details' => $messages,
            ], 422);
        }
    
        foreach ($schedule_hours as $schedule_hour) {
            if (sizeof($schedule_hour["children"]) > 0) {
                $day = StaffScheduleDay::where('day', $schedule_hour["day_name"])->first();
    
                $hours = [];
                foreach ($schedule_hour["children"] as $children) {
                    StaffSchedule::create([
                        "staff_id" => $staff->id,
                        "staff_schedule_day_id" => $day->id,
                        "staff_schedule_hour_id" => $children["item"]["id"],
                    ]);
                    $hours[] = StaffScheduleHour::find($children["item"]["id"])->hour_start;
                }
                $assigned_schedule[] = [
                    'day' => $day->day,
                    'hours' => $hours,
                ];
            }
        }
    
        return response()->json([
            'message' => 'Schedule assigned to ' . $staff->name,
            'schedule' => $assigned_schedule,
        ]);
    }
    

    public function update(Request $request)
    {
        $staff = Auth::guard('staff_api')->user();

        if (!$staff) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        $schedule_hours = $request->input('schedule_hours');
        $assigned_schedule = [];
        $taken_hours = [];
        $invalid_days = [];
        $invalid_hours = [];

        // Validar que los días existen
        foreach ($schedule_hours as $schedule_hour) {
            $day = StaffScheduleDay::where('day', $schedule_hour["day_name"])->first();
            if (!$day) {
                $invalid_days[] = $schedule_hour["day_name"];
            }
        }

        if (!empty($invalid_days)) {
            return response()->json([
                'message' => 'The following days do not exist: ' . implode(', ', $invalid_days),
            ], 422);
        }

        // Validar que las horas existen
        foreach ($schedule_hours as $schedule_hour) {
            foreach ($schedule_hour["children"] as $children) {
                $hour = StaffScheduleHour::find($children["item"]["id"]);
                if (!$hour) {
                    $invalid_hours[] = $children["item"]["hour_start"] ?? 'unknown';
                }
            }
        }

        if (!empty($invalid_hours)) {
            return response()->json([
                'message' => 'The following hours do not exist: ' . implode(', ', $invalid_hours),
            ], 422);
        }

        // ELIMINAR HORARIOS EXISTENTES
        StaffSchedule::where('staff_id', $staff->id)->delete();

        foreach ($schedule_hours as $schedule_hour) {
            if (sizeof($schedule_hour["children"]) > 0) {
                $day = StaffScheduleDay::where('day', $schedule_hour["day_name"])->first();
                foreach ($schedule_hour["children"] as $children) {
                    $existingSchedule = StaffSchedule::where('staff_id', $staff->id)
                        ->where('staff_schedule_day_id', $day->id)
                        ->where('staff_schedule_hour_id', $children["item"]["id"])
                        ->first();

                    if ($existingSchedule) {
                        $taken_hours[$schedule_hour["day_name"]][] = StaffScheduleHour::find($children["item"]["id"])->hour_start;
                    }
                }
            }
        }

        if (!empty($taken_hours)) {
            $messages = [];
            foreach ($taken_hours as $day => $hours) {
                $messages[] = "The following hours are already assigned for $day: " . implode(', ', $hours);
            }
            return response()->json([
                'message' => implode("\n", $messages),
            ], 422);
        }

        foreach ($schedule_hours as $schedule_hour) {
            if (sizeof($schedule_hour["children"]) > 0) {
                $day = StaffScheduleDay::where('day', $schedule_hour["day_name"])->first();

                $hours = [];
                foreach ($schedule_hour["children"] as $children) {
                    StaffSchedule::create([
                        "staff_id" => $staff->id,
                        "staff_schedule_day_id" => $day->id,
                        "staff_schedule_hour_id" => $children["item"]["id"],
                    ]);
                    $hours[] = StaffScheduleHour::find($children["item"]["id"])->hour_start;
                }
                $assigned_schedule[] = [
                    'day' => $day->day,
                    'hours' => $hours,
                ];
            }
        }

        return response()->json([
            'message' => 'Schedule updated for ' . $staff->name,
            'schedule' => $assigned_schedule,
        ]);
    }


    public function destroy(string $id)
    {
        $staff = Auth::guard('staff_api')->user();

        if (!$staff) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        $schedule_day = StaffScheduleDay::findOrFail($id);

        StaffSchedule::where('staff_id', $staff->id)
            ->where('staff_schedule_day_id', $schedule_day->id)
            ->delete();

        return response()->json([
            'message' => 'Schedules for the specified day deleted successfully'
        ], 200);
    }
}
