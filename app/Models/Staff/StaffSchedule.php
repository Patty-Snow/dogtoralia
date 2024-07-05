<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'staff_schedule_day_id',
        'staff_schedule_hour_id',
    ];

    public function hour()
    {
        return $this->belongsTo(StaffScheduleHour::class, 'staff_schedule_hour_id');
    }

    public function day()
    {
        return $this->belongsTo(StaffScheduleDay::class, 'staff_schedule_day_id');
    }
}
