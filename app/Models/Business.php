<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'phone_number', 
        'email', 
        'description', 
        'services', 
        'profile_photo', 
        'owner_id', 
        'address_id', 
        'availability_id'
    ];

    public function owner()
    {
        return $this->belongsTo(BusinessOwner::class, 'owner_id');
    }

    public function address()
    {
        return $this->belongsTo(Address::class, 'address_id');
    }

    public function availability()
    {
        return $this->belongsTo(Availability::class, 'availability_id');
    }
}
