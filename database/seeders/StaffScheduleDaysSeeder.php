<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Staff\StaffScheduleDay;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class StaffScheduleDaysSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        foreach ($days as $day) {
            StaffScheduleDay::create(['day' => $day]);
        }
    }
}
