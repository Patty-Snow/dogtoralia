<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'discount_price',
        'start_date',
        'end_date',
        'description',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
