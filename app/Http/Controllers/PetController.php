<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Pet;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Support\Facades\Log;


class PetController extends Controller
{
    public function index(Request $request, $pet_owner_id)
    {
        try {
            $perPage = $request->query('per_page', 20);

            if (Auth::guard('pet_owner_api')->check() && Auth::id() == $pet_owner_id) {
                $pets = Pet::where('pet_owner_id', $pet_owner_id)->paginate($perPage);
            } elseif (Auth::guard('business_owner_api')->check() || Auth::guard('staff_api')->check()) {
                $pets = Pet::withTrashed()->where('pet_owner_id', $pet_owner_id)->paginate($perPage);
            } else {
                throw new UnauthorizedException('Unauthorized access.');
            }

            // Formatear la fecha de nacimiento
            foreach ($pets as $pet) {
                $pet->birth_date = Carbon::parse($pet->birth_date)->format('d-m-Y');
            }

            return response()->json(['status' => 'success', 'pets' => $pets]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Error fetching pets', 'error' => $e->getMessage()], 500);
        }
    }

    public function indexAll(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 20);
            $pets = Pet::paginate($perPage);

            if ($pets->isEmpty()) {
                return response()->json(['status' => 'error', 'message' => 'No pets found'], 404);
            }

            // Formatear la fecha de nacimiento
            foreach ($pets as $pet) {
                $pet->birth_date = Carbon::parse($pet->birth_date)->format('d-m-Y');
            }

            return response()->json(['status' => 'success', 'pets' => $pets]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Error fetching pets', 'error' => $e->getMessage()], 500);
        }
    }

    public function myPets(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 20);

            if (Auth::guard('pet_owner_api')->check()) {
                $petOwnerId = Auth::id();
                $pets = Pet::where('pet_owner_id', $petOwnerId)->paginate($perPage);

                if ($pets->isEmpty()) {
                    return response()->json(['status' => 'error', 'message' => 'No pets found for this pet owner'], 404);
                }

                // Formatear la fecha de nacimiento
                foreach ($pets as $pet) {
                    $pet->birth_date = Carbon::parse($pet->birth_date)->format('d-m-Y');
                }

                return response()->json(['status' => 'success', 'pets' => $pets]);
            } else {
                throw new UnauthorizedException('Unauthorized access.');
            }
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Error fetching pets', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($pet_id)
    {
        try {
            if (Auth::guard('pet_owner_api')->check()) {
                $pet = Pet::withTrashed()->findOrFail($pet_id);

                if ($pet->pet_owner_id != Auth::id()) {
                    throw new UnauthorizedException('You do not have permission to access this resource.');
                }
            } elseif (Auth::guard('business_owner_api')->check() || Auth::guard('staff_api')->check()) {
                $pet = Pet::withTrashed()->findOrFail($pet_id);
            } else {
                throw new UnauthorizedException('Unauthorized access.');
            }

            // Formatear la fecha de nacimiento
            $pet->birth_date = Carbon::parse($pet->birth_date)->format('d-m-Y');

            return response()->json(['status' => 'success', 'pet' => $pet]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Error fetching pet', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $validatedData = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('pets')->where(function ($query) {
                        return $query->where('pet_owner_id', Auth::id());
                    }),
                ],
                'species' => 'required|string|max:255',
                'breed' => 'nullable|string|max:255',
                'birth_date' => 'nullable|date_format:d-m-Y',
                'color' => 'nullable|string|max:255',
                'gender' => 'nullable|string|max:255',
                'image' => 'sometimes|image|max:2028',
                'alt_text' => 'nullable|string|max:255'
            ]);

            if (!empty($validatedData['birth_date'])) {
                $validatedData['birth_date'] = Carbon::createFromFormat('d-m-Y', $validatedData['birth_date'])->format('Y-m-d');
            }

            if ($request->hasFile('image')) {
                $fileName = time() . '_' . $request->image->getClientOriginalName();
                $filePath = $request->image->storeAs('pet_images', $fileName, 'public');

                $image = new Image();
                $image->source_url = 'storage/' . $filePath;
                $image->alt_text = $request->alt_text;
                $image->save();

                $validatedData['photo_id'] = $image->id;
            }

            $validatedData['pet_owner_id'] = Auth::id();
            $pet = Pet::create($validatedData);
            $pet->birth_date = Carbon::parse($pet->birth_date)->format('d-m-Y');

            DB::commit();

            return response()->json(['status' => 'success', 'pet' => $pet]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Error creating pet', 'error' => $e->getMessage()], 500);
        }
    }



    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $validatedData = $request->validate([
                'name' => [
                    'sometimes',
                    'string',
                    'max:255',
                    Rule::unique('pets')->where(function ($query) {
                        return $query->where('pet_owner_id', Auth::id());
                    }),
                ],
                'species' => 'sometimes|string|max:255',
                'breed' => 'sometimes|string|max:255',
                'birth_date' => 'sometimes|date_format:d-m-Y',
                'color' => 'sometimes|string|max:255',
                'gender' => 'sometimes|string|max:255',
                'image' => 'sometimes|image|max:2028',
                'alt_text' => 'sometimes|string|max:255'
            ]);

            $pet = Pet::withTrashed()->where('id', $id)->where('pet_owner_id', Auth::id())->first();

            if (!$pet) {
                return response()->json(['status' => 'error', 'message' => 'Pet not found or you do not have permission to update this pet.'], 404);
            }

            if (!empty($validatedData['birth_date'])) {
                $validatedData['birth_date'] = Carbon::createFromFormat('d-m-Y', $validatedData['birth_date'])->format('Y-m-d');
            }

            if ($request->hasFile('image')) {
                if ($pet->photo_id) {
                    $oldImage = Image::find($pet->photo_id);
                    if ($oldImage) {
                        Storage::disk('public')->delete($oldImage->source_url);
                        $oldImage->delete();
                    }
                }

                $fileName = time() . '_' . $request->image->getClientOriginalName();
                $filePath = $request->image->storeAs('pet_images', $fileName, 'public');

                $image = new Image();
                $image->source_url = 'storage/' . $filePath;
                $image->alt_text = $request->alt_text;
                $image->save();

                $validatedData['photo_id'] = $image->id;
            }

            $pet->update($validatedData);

            DB::commit();

            return response()->json(['status' => 'success', 'pet' => $pet]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Error updating pet', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $pet = Pet::where('id', $id)->where('pet_owner_id', Auth::id())->first();

            if (!$pet) {
                return response()->json(['status' => 'error', 'message' => 'Pet not found or you do not have permission to delete this pet.'], 404);
            }

            $pet->delete();

            return response()->json(['status' => 'success', 'message' => 'Pet deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Error deleting pet', 'error' => $e->getMessage()], 500);
        }
    }

    public function trashed(Request $request)
{
    try {
        $perPage = $request->query('per_page', 20);

        if (Auth::guard('pet_owner_api')->check()) {
            Log::info('Pet owner authenticated. Fetching pets for owner ID.', ['id' => Auth::id()]);
            $pets = Pet::onlyTrashed()->where('pet_owner_id', Auth::id())->paginate($perPage);
        } elseif (Auth::guard('business_owner_api')->check() || Auth::guard('staff_api')->check()) {
            Log::info('Business or staff authenticated.');
            $pets = Pet::onlyTrashed()->paginate($perPage);
        } else {
            throw new UnauthorizedException('Unauthorized access.');
        }

        if ($pets->isEmpty()) {
            return response()->json(['status' => 'error', 'message' => 'No trashed pets found'], 404);
        }

        // Formatear la fecha de nacimiento
        foreach ($pets as $pet) {
            if ($pet->birth_date) {
                $pet->birth_date = Carbon::parse($pet->birth_date)->format('d-m-Y');
            }
        }

        return response()->json(['status' => 'success', 'pets' => $pets]);
    } catch (\Exception $e) {
        Log::error('Error fetching trashed pets.', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json(['status' => 'error', 'message' => 'Error fetching trashed pets', 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 500);
    }
}

    




    public function restore($id)
    {
        try {
            $pet = Pet::withTrashed()->where('id', $id)->where('pet_owner_id', Auth::id())->first();

            if (!$pet) {
                return response()->json(['status' => 'error', 'message' => 'Pet not found or you do not have permission to restore this pet.'], 404);
            }

            $pet->restore();

            return response()->json(['status' => 'success', 'message' => 'Pet restored successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Error restoring pet', 'error' => $e->getMessage()], 500);
        }
    }

    public function forceDelete($id)
    {
        try {
            $pet = Pet::withTrashed()->where('id', $id)->where('pet_owner_id', Auth::id())->first();

            if (!$pet) {
                return response()->json(['status' => 'error', 'message' => 'Pet not found or you do not have permission to delete this pet permanently.'], 404);
            }

            if ($pet->photo_id) {
                $image = Image::find($pet->photo_id);
                if ($image) {
                    Storage::disk('public')->delete($image->source_url);
                    $image->delete();
                }
            }

            $pet->forceDelete();

            return response()->json(['status' => 'success', 'message' => 'Pet permanently deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Error deleting pet permanently', 'error' => $e->getMessage()], 500);
        }
    }
}
