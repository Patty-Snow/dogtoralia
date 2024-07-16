<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'currency',
        'max_services_simultaneously',
        'duration',
        'business_id',
    ];

    public function offer()
    {
        return $this->hasOne(Offer::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }
    
}
