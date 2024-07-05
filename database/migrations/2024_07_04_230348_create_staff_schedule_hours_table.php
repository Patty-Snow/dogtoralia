<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStaffScheduleHoursTable extends Migration
{
    public function up()
    {
        Schema::create('staff_schedule_hours', function (Blueprint $table) {
            $table->id();
            $table->string('hour_start');
            $table->string('hour_end');
            $table->string('hour');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('staff_schedule_hours');
    }
}

