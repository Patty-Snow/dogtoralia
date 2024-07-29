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

        // Configura la localización a español para obtener el día de la semana en español
        $date->locale('es');
        $dayOfWeek = $date->isoFormat('dddd'); // 'dddd' obtiene el nombre completo del día de la semana
        $normalizedDayOfWeek = mb_strtolower($dayOfWeek); // Convierte a minúsculas

        // Log de depuración
        Log::debug('Datos de la solicitud: ', [
            'date' => $date->toDateString(),
            'time' => $time->toTimeString(),
            'dayOfWeek' => $dayOfWeek,
            'formattedDateTime' => $dateTime->format('Y-m-d H:i:s'),
            'normalizedDayOfWeek' => $normalizedDayOfWeek
        ]);

        // Verifica el horario de trabajo del negocio
        $businessSchedule = BusinessSchedule::where('business_id', $businessId)
            ->where('day_of_week', $normalizedDayOfWeek)
            ->first();

        if (!$businessSchedule) {
            return response()->json(['status' => 'error', 'message' => 'Business is closed on this day'], 400);
        }

        // Log para verificar los time_slots
        Log::debug('Time slots: ', [
            'time_slots' => $businessSchedule->time_slots
        ]);

        // Verifica si el horario solicitado está dentro de algún time_slot
        $isOpen = false;

        foreach ($businessSchedule->time_slots as $slot) {
            $slotStart = Carbon::createFromFormat('H:i', $slot['start_time']);
            $slotEnd = Carbon::createFromFormat('H:i', $slot['end_time']);

            // Log para verificar cada slot
            Log::debug('Verificando time slot: ', [
                'slotStart' => $slotStart->format('H:i'),
                'slotEnd' => $slotEnd->format('H:i'),
                'dateTime' => $dateTime->format('H:i')
            ]);

            // Asegúrate de que la comparación sea correcta, y verifica si la hora está dentro del intervalo
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
        // Obtén la fecha y hora actuales
        $now = Carbon::now();
        $dayOfWeek = $now->locale('es')->isoFormat('dddd'); // Obtiene el día de la semana en español
        $normalizedDayOfWeek = mb_strtolower($dayOfWeek); // Convierte a minúsculas
        $currentTime = $now->format('H:i');

        // Log de depuración
        Log::debug('Verificando negocios abiertos: ', [
            'currentDayOfWeek' => $normalizedDayOfWeek,
            'currentTime' => $currentTime
        ]);

        // Busca los horarios de negocios que estén abiertos a la hora actual
        $openBusinesses = BusinessSchedule::where('day_of_week', $normalizedDayOfWeek)
            ->whereJsonContains('time_slots', function ($query) use ($currentTime) {
                $query->where('start_time', '<=', $currentTime)
                    ->where('end_time', '>=', $currentTime);
            })
            ->with('business:id,name') // Obtén solo el ID y el nombre del negocio
            ->get();

        // Filtra los resultados para asegurarse de que los negocios existan
        $openBusinesses = $openBusinesses->filter(function ($schedule) {
            return $schedule->business !== null;
        });

        // Verifica si hay negocios abiertos
        if ($openBusinesses->isEmpty()) {
            return response()->json(['status' => 'error', 'message' => 'No businesses are open at this time'], 404);
        }

        // Construye la respuesta con los datos necesarios
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
