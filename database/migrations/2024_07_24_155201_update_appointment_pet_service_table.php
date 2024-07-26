<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAppointmentPetServiceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('appointment_pet_service', function (Blueprint $table) {
            $table->timestamp('appointment_time')->after('service_id');
            $table->timestamp('appointment_end_time')->after('appointment_time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('appointment_pet_service', function (Blueprint $table) {
            $table->dropColumn('appointment_time');
            $table->dropColumn('appointment_end_time');
        });
    }
}
