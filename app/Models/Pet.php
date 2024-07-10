<?php
namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;



class Pet extends Model
{
    use HasFactory, SoftDeletes;


    protected $fillable = [
        'pet_owner_id', 'name', 'species', 'breed', 'birth_date', 'color', 'gender', 'photo_id'
    ];


    public function owner()
    {
        return $this->belongsTo(PetOwner::class, 'pet_owner_id');
    }

    public function photo()
    {
        return $this->belongsTo(Image::class, 'photo_id');
    }
}
