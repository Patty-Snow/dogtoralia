<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffScheduleHour extends Model
{
    use HasFactory;

    protected $fillable = [
        'hour_start',
        'hour_end',
        'hour',
    ];

    public function schedules()
    {
        return $this->hasMany(StaffSchedule::class);
    }
}
