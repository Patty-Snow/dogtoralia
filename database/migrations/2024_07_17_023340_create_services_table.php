<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServicesTable extends Migration
{
    public function up()
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name', 45);
            $table->text('description');
            $table->decimal('price', 10, 2);
            $table->string('currency', 3);
            $table->integer('max_services_simultaneously');
            $table->integer('duration');
            $table->string('category')->default('services'); 
            $table->decimal('discount_price', 10, 2)->nullable(); 
            $table->dateTime('offer_start')->nullable(); 
            $table->dateTime('offer_end')->nullable(); 
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('services');
    }
}
