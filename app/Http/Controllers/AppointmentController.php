<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\BusinessSchedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AppointmentController extends Controller
{
    public function checkAvailability($serviceId, $dateTime)
{
    $service = Service::findOrFail($serviceId);
    $dateTime = Carbon::parse($dateTime);

    $dayOfWeek = $dateTime->format('l'); 

    // Verificar el horario de trabajo del negocio
    $businessSchedule = BusinessSchedule::where('business_id', $service->business_id)
        ->where('day_of_week', $dayOfWeek)
        ->where('start_time', '<=', $dateTime->format('H:i:s'))
        ->where('end_time', '>=', $dateTime->copy()->addMinutes($service->duration)->format('H:i:s'))
        ->first();

    if (!$businessSchedule) {
        return response()->json(['status' => 'error', 'message' => 'Business is closed at this time'], 400);
    }

    // Verificar las reservas existentes para el mismo servicio en la misma hora
    $appointments = DB::table('appointment_pet_service')
        ->where('service_id', $serviceId)
        ->where('appointment_time', $dateTime)
        ->count();

    if ($appointments >= $service->max_services_simultaneously) {
        return response()->json(['status' => 'error', 'message' => 'No availability at this time'], 400);
    }

    return response()->json(['status' => 'success', 'message' => 'Service is available at this time']);
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
