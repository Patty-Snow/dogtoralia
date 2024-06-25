<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessOwnersTable extends Migration
{
    public function up()
    {
        Schema::create('business_owners', function (Blueprint $table) {
            $table->id();
            $table->string('name', 45);
            $table->string('last_name');
            $table->string('email', 70)->unique();
            $table->string('password', 255);
            $table->string('phone_number', 13);
            $table->string('rfc', 13)->unique();
            $table->text('profile_photo')->nullable();
            $table->softDeletes();
            $table->timestamps();

        });
    }

    public function down()
    {
        Schema::dropIfExists('business_owners');
    }
}
