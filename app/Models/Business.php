<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Business extends Model
{
    use HasFactory, SoftDeletes;

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

    public function address()
    {
        return $this->hasOne(Address::class, 'business_id');

    public function services()
    {
        return $this->hasMany(Service::class);

    }
}

