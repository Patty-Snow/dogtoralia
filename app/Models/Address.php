<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'city',
        'state',
        'postal_code',
        'references',
        'latitude',
        'longitude',
        'formatted_address',
        'pet_owner_id',
        'business_id'
    ];

    public function businesses()
    {
        return $this->belongsToMany(Business::class, 'business_address');
    }

    public function petOwners()
    {
        return $this->belongsToMany(PetOwner::class, 'pet_owner_address');
    }
}
