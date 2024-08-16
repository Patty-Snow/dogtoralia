<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\BusinessSchedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;


class AppointmentController extends Controller
{
    public function checkAvailability(Request $request, $businessId)
    {
        $request->validate([
            'date' => 'required|date_format:d-m-Y',
            'time' => 'required|date_format:H:i',
        ]);

        $date = Carbon::createFromFormat('d-m-Y', $request->date);
        $time = Carbon::createFromFormat('H:i', $request->time);
        $dateTime = $date->setTime($time->hour, $time->minute);

        $date->locale('es');
        $dayOfWeek = $date->isoFormat('dddd'); 
        $normalizedDayOfWeek = mb_strtolower($dayOfWeek); 

        Log::debug('Datos de la solicitud: ', [
            'date' => $date->toDateString(),
            'time' => $time->toTimeString(),
            'dayOfWeek' => $dayOfWeek,
            'formattedDateTime' => $dateTime->format('Y-m-d H:i:s'),
            'normalizedDayOfWeek' => $normalizedDayOfWeek
        ]);

        $businessSchedule = BusinessSchedule::where('business_id', $businessId)
            ->where('day_of_week', $normalizedDayOfWeek)
            ->first();

        if (!$businessSchedule) {
            return response()->json(['status' => 'error', 'message' => 'Business is closed on this day'], 400);
        }

        Log::debug('Time slots: ', [
            'time_slots' => $businessSchedule->time_slots
        ]);

        $isOpen = false;

        foreach ($businessSchedule->time_slots as $slot) {
            $slotStart = Carbon::createFromFormat('H:i', $slot['start_time']);
            $slotEnd = Carbon::createFromFormat('H:i', $slot['end_time']);

            Log::debug('Verificando time slot: ', [
                'slotStart' => $slotStart->format('H:i'),
                'slotEnd' => $slotEnd->format('H:i'),
                'dateTime' => $dateTime->format('H:i')
            ]);

            if ($dateTime->format('H:i') >= $slotStart->format('H:i') && $dateTime->format('H:i') <= $slotEnd->format('H:i')) {
                $isOpen = true;
                break;
            }
        }

        if (!$isOpen) {
            return response()->json(['status' => 'error', 'message' => 'Business is closed at this time'], 400);
        }

        return response()->json(['status' => 'success', 'message' => 'Business is open at this time']);
    }



    public function indexOpenBusinesses()
    {
        $now = Carbon::now();
        $dayOfWeek = $now->locale('es')->isoFormat('dddd'); 
        $normalizedDayOfWeek = mb_strtolower($dayOfWeek); 
        $currentTime = $now->format('H:i');

        
        Log::debug('Verificando negocios abiertos: ', [
            'currentDayOfWeek' => $normalizedDayOfWeek,
            'currentTime' => $currentTime
        ]);

        $openBusinesses = BusinessSchedule::where('day_of_week', $normalizedDayOfWeek)
            ->whereJsonContains('time_slots', function ($query) use ($currentTime) {
                $query->where('start_time', '<=', $currentTime)
                    ->where('end_time', '>=', $currentTime);
            })
            ->with('business:id,name') 
            ->get();

        $openBusinesses = $openBusinesses->filter(function ($schedule) {
            return $schedule->business !== null;
        });

        
        if ($openBusinesses->isEmpty()) {
            return response()->json(['status' => 'error', 'message' => 'No businesses are open at this time'], 404);
        }

        
        $response = $openBusinesses->map(function ($schedule) {
            return [
                'business_id' => $schedule->business_id,
                'business_name' => $schedule->business->name,
                'day_of_week' => $schedule->day_of_week,
                'time_slots' => collect($schedule->time_slots)->map(function ($slot) {
                    return [
                        'start_time' => $slot['start_time'],
                        'end_time' => $slot['end_time']
                    ];
                }),
            ];
        });

        return response()->json(['status' => 'success', 'data' => $response]);
    }


}
