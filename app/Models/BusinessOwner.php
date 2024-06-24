<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class BusinessOwner extends Model 
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'last_name',
        'email',
        'password',
        'phone_number',
        'rfc',
        'registration_date',
        'profile_photo',
    ];

    public $timestamps = false; 

    protected $hidden = [
        'password',
    ];
}
