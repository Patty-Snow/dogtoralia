<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Address;

class GeolocationController extends Controller
{
    public function getAddress(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lon' => 'required|numeric',
            'pet_owner_id' => 'required|exists:pet_owners,id',
        ]);

        $lat = $request->input('lat');
        $lon = $request->input('lon');
        $petOwnerId = $request->input('pet_owner_id');

        $url = "https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat={$lat}&lon={$lon}&addressdetails=1";

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'YourAppName/1.0 (your-email@example.com)'
            ])->timeout(60)->retry(3, 1000)->get($url);

            if ($response->failed()) {
                \Log::error('Nominatim API request failed', [
                    'url' => $url,
                    'response_status' => $response->status(),
                    'response_body' => $response->body(),
                ]);
                return response()->json(['error' => 'Error al obtener la dirección'], 500);
            }

            $data = $response->json();
            \Log::info('Nominatim Response', ['response' => $data]);

            if (!isset($data['address'])) {
                \Log::error('Nominatim API did not return address data', ['response' => $data]);
                return response()->json(['error' => 'No se encontró la dirección'], 500);
            }

            $direccion = $data['display_name'] ?? 'No se encontró la dirección';
            $addressComponents = $this->extractAddressComponents($data['address']);

            // Guardar la dirección en la base de datos
            $address = Address::create([
                'city' => $addressComponents['city'],
                'state' => $addressComponents['state'] ?? $addressComponents['city'],
                'postal_code' => $addressComponents['postal_code'],
                'references' => $addressComponents['references'],
                'latitude' => $lat,
                'longitude' => $lon,
                'formatted_address' => $direccion,
                'pet_owner_id' => $petOwnerId,
            ]);

            return response()->json(['direccion' => $direccion, 'address' => $address]);

        } catch (\Exception $e) {
            \Log::error('Error al obtener la dirección', ['exception' => $e]);
            return response()->json(['error' => 'Error al obtener la dirección', 'message' => $e->getMessage()], 500);
        }
    }

    private function extractAddressComponents($address)
    {
        \Log::info('Address Components', $address);

        return [
            'city' => $address['city'] ?? $address['town'] ?? $address['village'] ?? $address['municipality'] ?? $address['borough'] ?? $address['suburb'] ?? $address['neighbourhood'] ?? null,
            'state' => $address['state'] ?? $address['region'] ?? $address['state_district'] ?? $address['province'] ?? $address['county'] ?? null,
            'postal_code' => $address['postcode'] ?? null,
            'references' => $address['road'] ?? $address['residential'] ?? $address['path'] ?? $address['pedestrian'] ?? null,
        ];
    }
}
