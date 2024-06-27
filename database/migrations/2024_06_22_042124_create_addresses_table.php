<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAddressesTable extends Migration
{
    public function up()
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->integer('postal_code');
            $table->text('references')->nullable();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 10, 8);
            $table->text('formatted_address');
            $table->unsignedBigInteger('pet_owner_id');
            $table->foreign('pet_owner_id')->references('id')->on('pet_owners')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('addresses');
    }
}

