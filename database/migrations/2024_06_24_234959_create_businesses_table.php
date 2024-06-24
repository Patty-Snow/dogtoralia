<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessesTable extends Migration
{
    public function up()
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 45);
            $table->string('phone_number', 45)->unique();
            $table->string('email', 70)->unique();
            $table->string('description', 45);
            $table->string('services', 45);
            $table->text('profile_photo');
            $table->foreignId('business_owner_id')->constrained('business_owners'); 
            $table->foreignId('address_id')->constrained('addresses'); 
            $table->foreignId('availability_id')->constrained('availabilities'); 
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('businesses');
    }
}
