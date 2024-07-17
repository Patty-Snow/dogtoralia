<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use App\Models\Address;
use App\Models\Business;

class GeolocationController extends Controller
{
    /**
     * Establece una dirección para un negocio.
     *
     * @param Request $request
     * @param int $business_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function setAddress(Request $request, $business_id)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lon' => 'required|numeric',
            'references' => 'sometimes|nullable|string',
        ]);

        $lat = $request->input('lat');
        $lon = $request->input('lon');
        $references = $request->input('references');

        // Validar que el business_id existe
        $business = Business::find($business_id);
        if (!$business) {
            return response()->json(['error' => 'No business found with the provided ID'], 404);
        }

        // Validar que el business_id pertenece al business owner autenticado
        $user = Auth::guard('business_owner_api')->user();
        if ($business->business_owner_id !== $user->id) {
            return response()->json(['error' => 'The business does not belong to the authenticated user'], 403);
        }

        $url = "https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat={$lat}&lon={$lon}&addressdetails=1";

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'MyApp/1.0 (example@example.com)'
            ])->timeout(60)->retry(3, 1000)->get($url);

            if ($response->failed()) {
                \Log::error('Nominatim API request failed', [
                    'url' => $url,
                    'response_status' => $response->status(),
                    'response_body' => $response->body(),
                ]);
                
                if ($response->status() == 403) {
                    return response()->json(['error' => 'Access blocked by Nominatim API'], 403);
                }
                
                return response()->json(['error' => 'Failed to fetch address'], 500);
            }

            $data = $response->json();
            \Log::info('Nominatim Response', ['response' => $data]);

            if (!isset($data['address'])) {
                \Log::error('Nominatim API did not return address data', ['response' => $data]);
                return response()->json(['error' => 'Address not found'], 500);
            }

            $direccion = $data['display_name'] ?? 'Address not found';
            $addressComponents = $this->extractAddressComponents($data['address'], $references);

            // Verificar si ya existe una dirección para este negocio
            $existingAddress = Address::where('business_id', $business_id)->first();

            if ($existingAddress) {
                // Actualizar los campos existentes de la dirección
                $existingAddress->city = $addressComponents['city'];
                $existingAddress->state = $addressComponents['state'] ?? $addressComponents['city'];
                $existingAddress->postal_code = $addressComponents['postal_code'];
                $existingAddress->references = $addressComponents['references'];
                $existingAddress->latitude = $lat;
                $existingAddress->longitude = $lon;
                $existingAddress->formatted_address = $direccion;
                $existingAddress->save();

                return response()->json(['message' => 'Address updated successfully', 'address' => $existingAddress]);
            } else {
                // Crear una nueva dirección si no existe una previa
                $newAddress = Address::create([
                    'city' => $addressComponents['city'],
                    'state' => $addressComponents['state'] ?? $addressComponents['city'],
                    'postal_code' => $addressComponents['postal_code'],
                    'references' => $addressComponents['references'],
                    'latitude' => $lat,
                    'longitude' => $lon,
                    'formatted_address' => $direccion,
                    'business_id' => $business_id,
                ]);

                return response()->json(['message' => 'Address created successfully', 'address' => $newAddress]);
            }

        } catch (\Exception $e) {
            \Log::error('Error fetching address', ['exception' => $e]);
            return response()->json(['error' => 'Error fetching address', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Establece una dirección para un pet owner.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function setAddressForPetOwner(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lon' => 'required|numeric',
            'references' => 'sometimes|nullable|string',
        ]);

        $lat = $request->input('lat');
        $lon = $request->input('lon');
        $references = $request->input('references');

        // Obtener el pet_owner autenticado
        $petOwner = Auth::guard('pet_owner_api')->user();

        if (!$petOwner) {
            return response()->json(['error' => 'No authenticated pet owner found'], 401);
        }

        $url = "https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat={$lat}&lon={$lon}&addressdetails=1";

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'MyApp/1.0 (example@example.com)'
            ])->timeout(60)->retry(3, 1000)->get($url);

            if ($response->failed()) {
                \Log::error('Nominatim API request failed', [
                    'url' => $url,
                    'response_status' => $response->status(),
                    'response_body' => $response->body(),
                ]);
                
                if ($response->status() == 403) {
                    return response()->json(['error' => 'Access blocked by Nominatim API'], 403);
                }
                
                return response()->json(['error' => 'Failed to fetch address'], 500);
            }

            $data = $response->json();
            \Log::info('Nominatim Response', ['response' => $data]);

            if (!isset($data['address'])) {
                \Log::error('Nominatim API did not return address data', ['response' => $data]);
                return response()->json(['error' => 'Address not found'], 500);
            }

            $direccion = $data['display_name'] ?? 'Address not found';
            $addressComponents = $this->extractAddressComponents($data['address'], $references);

            // Crear una nueva dirección asociada al pet_owner
            $newAddress = Address::create([
                'city' => $addressComponents['city'],
                'state' => $addressComponents['state'] ?? $addressComponents['city'],
                'postal_code' => $addressComponents['postal_code'],
                'references' => $addressComponents['references'],
                'latitude' => $lat,
                'longitude' => $lon,
                'formatted_address' => $direccion,
                'pet_owner_id' => $petOwner->id,
            ]);

            return response()->json(['message' => 'Address created successfully', 'address' => $newAddress]);

        } catch (\Exception $e) {
            \Log::error('Error fetching address', ['exception' => $e]);
            return response()->json(['error' => 'Error fetching address', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Extrae los componentes de dirección relevantes del objeto de dirección.
     *
     * @param array $address
     * @param string|null $references
     * @return array
     */
    private function extractAddressComponents($address, $references)
    {
        \Log::info('Address Components', $address);

        return [
            'city' => $address['city'] ?? $address['town'] ?? $address['village'] ?? $address['municipality'] ?? $address['borough'] ?? $address['suburb'] ?? $address['neighbourhood'] ?? null,
            'state' => $address['state'] ?? $address['region'] ?? $address['state_district'] ?? $address['province'] ?? $address['county'] ?? null,
            'postal_code' => isset($address['postcode']) ? (string)$address['postcode'] : null, // Convertir a string
            'references' => $references ?? null,
        ];
    }
}
