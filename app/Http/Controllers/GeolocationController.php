<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GeolocationController extends Controller
{
    public function getAddress(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lon' => 'required|numeric',
        ]);

        $lat = $request->input('lat');
        $lon = $request->input('lon');

        $url = "https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat={$lat}&lon={$lon}";

        try {
            $response = Http::get($url);

            if ($response->failed()) {
                return response()->json(['error' => 'Error al obtener la dirección'], 500);
            }

            $data = $response->json();
            $direccion = $data['display_name'] ?? 'No se encontró la dirección';

            return response()->json(['direccion' => $direccion]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener la dirección', 'message' => $e->getMessage()], 500);
        }
    }
}
