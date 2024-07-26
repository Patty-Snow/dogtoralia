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





    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'business_id' => 'required|exists:businesses,id',
            'pet_owner_id' => 'required|exists:pet_owners,id',
            'appointment_time' => 'required|date_format:Y-m-d H:i:s',
            'pets_services' => 'required|array',
            'pets_services.*.pet_id' => 'required|exists:pets,id',
            'pets_services.*.service_id' => 'required|exists:services,id',
        ]);

        $appointmentTime = Carbon::createFromFormat('Y-m-d H:i:s', $validatedData['appointment_time']);

        // Verificar disponibilidad
        foreach ($validatedData['pets_services'] as $petService) {
            $availability = $this->checkAvailability($petService['service_id'], $appointmentTime);
            if ($availability->status() !== 200) {
                return $availability;
            }
        }

        // Crear la cita
        $appointment = Appointment::create([
            'business_id' => $validatedData['business_id'],
            'pet_owner_id' => $validatedData['pet_owner_id'],
            'appointment_time' => $appointmentTime,
            'status' => 'scheduled',
        ]);

        // Añadir los servicios y mascotas a la cita
        foreach ($validatedData['pets_services'] as $petService) {
            $appointment->pets()->attach($petService['pet_id'], ['service_id' => $petService['service_id']]);
        }

        return response()->json(['status' => 'success', 'appointment' => $appointment]);
    }

    public function index(Request $request)
    {
        $user = Auth::guard('pet_owner_api');
        $perPage = $request->query('per_page', 20);

        $appointments = Appointment::with(['pets', 'services', 'business'])
            ->where('pet_owner_id', $user->id)
            ->paginate($perPage);

        return response()->json($appointments);
    }
}
