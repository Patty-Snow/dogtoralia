<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pet extends Model
{
    use HasFactory, SoftDeletes;


    protected $fillable = [
        'pet_owner_id', 'name', 'species', 'breed', 'birth_date', 'color', 'gender'
    ];

    public function owner()
    {
        return $this->belongsTo(PetOwner::class, 'pet_owner_id');
    }
}
