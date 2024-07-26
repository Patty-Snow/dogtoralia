<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;

class BusinessSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'day_of_week',
        'start_time',
        'end_time',
        'time_slots', // JSON field for storing multiple time slots
     
    ];

    protected $casts = [
        'time_slots' => 'array'
    ];
    

    public function business()
    {
        return $this->belongsTo(Business::class);
    }


}
