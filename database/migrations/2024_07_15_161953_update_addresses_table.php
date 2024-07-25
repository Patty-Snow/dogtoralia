<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateAddressesTable extends Migration
{
    public function up()
    {
        Schema::table('addresses', function (Blueprint $table) {
            // Eliminar la clave foránea de pet_owner_id antes de hacer cambios
            $table->dropForeign(['pet_owner_id']);

            // Hacer nullable el campo postal_code
            $table->string('postal_code')->nullable()->change();

            // Hacer nullable el campo pet_owner_id
            $table->unsignedBigInteger('pet_owner_id')->nullable()->change();

            // Agregar la columna business_id
            $table->unsignedBigInteger('business_id')->nullable()->after('pet_owner_id');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');

            // Volver a agregar la clave foránea para pet_owner_id
            $table->foreign('pet_owner_id')->references('id')->on('pet_owners')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('addresses', function (Blueprint $table) {

            // Eliminar la clave foránea de business_id
            $table->dropForeign(['business_id']);

            // Eliminar la clave foránea de pet_owner_id
            $table->dropForeign(['pet_owner_id']);
            // Vaciar la tabla antes de revertir los cambios
            DB::table('addresses')->truncate();
            // Revertir el cambio de postal_code a no nullable
            $table->string('postal_code')->nullable(false)->change();

            // Revertir el cambio de pet_owner_id a no nullable
            $table->unsignedBigInteger('pet_owner_id')->nullable(false)->change();

            // Eliminar la columna business_id
            $table->dropColumn('business_id');

            // Volver a agregar la clave foránea para pet_owner_id
            $table->foreign('pet_owner_id')->references('id')->on('pet_owners')->onDelete('cascade');
        });
    }
}
