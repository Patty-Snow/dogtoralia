<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffScheduleDay extends Model
{
    use HasFactory;

    protected $fillable = ['day'];

    public function schedules()
    {
        return $this->hasMany(StaffSchedule::class, 'staff_schedule_day_id');
    }
}
