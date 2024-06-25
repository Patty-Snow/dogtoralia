<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStylistsVeterinariansTable extends Migration
{
    public function up()
    {
        Schema::create('stylists_veterinarians', function (Blueprint $table) {
            $table->id();
            $table->string('name', 45);
            $table->string('last_name', 45);
            $table->string('surname', 45);
            $table->string('email', 70)->unique();
            $table->string('password', 255);
            $table->string('phone_number', 13); 
            $table->text('profile_photo');
            $table->foreignId('business_id')->constrained('businesses'); 
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('stylists_veterinarians');
    }
}
