<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pet extends Model
{
    use HasFactory;

    protected $fillable = [
        'pet_owner_id', 'name', 'species', 'breed', 'birth_date', 'color', 'gender'
    ];

    public function owner()
    {
        return $this->belongsTo(PetOwner::class, 'pet_owner_id');
    }
}
