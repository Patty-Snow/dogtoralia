<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class BusinessOwner extends Authenticatable implements JWTSubject
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'last_name',
        'email',
        'password',
        'phone_number',
        'rfc',
        'profile_photo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public $timestamps = true;

    /**
     * Get the identifier that will be stored in the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
