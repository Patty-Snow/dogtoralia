<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_url',
        'alt_text',
    ];

    // Define the inverse relationship with the Pet model
    public function pets()
    {
        return $this->hasMany(Pet::class, 'photo_id');
    }
}
