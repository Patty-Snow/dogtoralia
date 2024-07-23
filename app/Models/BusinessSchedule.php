<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id', 
        'day_of_week', 
        'start_time', 
        'end_time'
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
