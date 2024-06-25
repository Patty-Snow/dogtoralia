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
        'profile_photo',
        'business_owner_id',
    ];

    public function owner()
    {
        return $this->belongsTo(BusinessOwner::class, 'business_owner_id');
    }
}
