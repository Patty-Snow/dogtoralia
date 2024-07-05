<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStaffScheduleDaysTable extends Migration
{
    public function up()
    {
        Schema::create('staff_schedule_days', function (Blueprint $table) {
            $table->id();
            $table->string('day')->unique();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('staff_schedule_days');
    }
}
