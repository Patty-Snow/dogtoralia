<?php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Direction extends Model
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
    ];

    public function petOwner()
    {
        return $this->belongsTo(PetOwner::class);
    }
}
