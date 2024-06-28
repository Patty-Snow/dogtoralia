<?php
namespace App\Http\Controllers;

use App\Models\Pet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PetController extends Controller
{
    public function index()
    {
        $pets = Pet::where('pet_owner_id', Auth::id())->get();
        return response()->json($pets);
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'species' => 'required|string|max:255',
                'breed' => 'nullable|string|max:255',
                'birth_date' => 'nullable|date_format:d-m-Y', // Validar el formato de la fecha como día-mes-año
                'color' => 'nullable|string|max:255',
                'gender' => 'nullable|string|max:255',
            ]);

            // Convertir la fecha al formato Y-m-d antes de guardar
            if (isset($validatedData['birth_date'])) {
                $validatedData['birth_date'] = Carbon::createFromFormat('d-m-Y', $validatedData['birth_date'])->format('Y-m-d');
            }

            // Agregar el pet_owner_id del usuario autenticado
            $validatedData['pet_owner_id'] = Auth::id();

            $pet = Pet::create($validatedData);

            return response()->json($pet, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error creating pet: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $pet = Pet::where('id', $id)
                        ->where('pet_owner_id', Auth::id())
                        ->firstOrFail();

            return response()->json($pet);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error fetching pet: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'sometimes|string|max:255',
                'species' => 'sometimes|string|max:255',
                'breed' => 'sometimes|string|max:255',
                'birth_date' => 'sometimes|date_format:d-m-Y', // Validar el formato de la fecha como día-mes-año
                'color' => 'sometimes|string|max:255',
                'gender' => 'sometimes|string|max:255',
            ]);

            // Convertir la fecha al formato Y-m-d antes de guardar
            if (isset($validatedData['birth_date'])) {
                $validatedData['birth_date'] = Carbon::createFromFormat('d-m-Y', $validatedData['birth_date'])->format('Y-m-d');
            }

            $pet = Pet::where('id', $id)
                        ->where('pet_owner_id', Auth::id())
                        ->firstOrFail();

            $pet->update($validatedData);

            return response()->json($pet);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error updating pet: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $pet = Pet::where('id', $id)
                        ->where('pet_owner_id', Auth::id())
                        ->firstOrFail();

            $pet->delete();

            return response()->json(['message' => 'Pet deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error deleting pet: ' . $e->getMessage()], 500);
        }
    }
}
