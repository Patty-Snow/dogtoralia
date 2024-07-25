<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'pet_owner_id',
        'appointment_time',
        'status',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function owner()
    {
        return $this->belongsTo(PetOwner::class, 'pet_owner_id');
    }

    public function pets()
    {
        return $this->belongsToMany(Pet::class, 'appointment_pet_service')
        
                    ->withPivot('service_id')
                    ->withTimestamps();
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'appointment_pet_service')
                    ->withPivot('pet_id')
                    ->withTimestamps();
    }
}
