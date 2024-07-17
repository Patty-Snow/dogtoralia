<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Image;
use App\Models\Pet;
use App\Models\PetOwner;
use App\Models\Staff;
use App\Models\BusinessOwner;

class ImageController extends Controller
{
    public function uploadPetImage(Request $request, $id)
    {
        return $this->uploadImage($request, Pet::class, $id);
    }

    public function uploadPetOwnerImage(Request $request, $id)
    {
        return $this->uploadImage($request, PetOwner::class, $id);
    }

    public function uploadStaffImage(Request $request, $id)
    {
        return $this->uploadImage($request, Staff::class, $id);
    }

    public function uploadBusinessOwnerImage(Request $request, $id)
    {
        return $this->uploadImage($request, BusinessOwner::class, $id);
    }

    private function uploadImage(Request $request, $modelClass, $id)
    {
        $request->validate([
            'image' => 'required|image',
            'alt_text' => 'nullable|string',
        ]);

        $imageFile = $request->file('image');
        $imagePath = $imageFile->store('images', 'public'); // Guardar la imagen en el almacenamiento público

        $image = new Image();
        $image->source_url = $imagePath;
        $image->alt_text = $request->input('alt_text', '');

        // Asociar la imagen con el modelo correcto
        $model = $modelClass::findOrFail($id);
        $model->images()->save($image);

        return response()->json(['success' => 'Image uploaded successfully', 'image' => $image], 201);
    }
}
