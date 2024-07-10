<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pets', function (Blueprint $table) {
            $table->softDeletes(); 
            $table->unsignedBigInteger('photo_id')->nullable()->after('id');

            // Adding the foreign key constraint
            $table->foreign('photo_id')->references('id')->on('images')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pets', function (Blueprint $table) {
            $table->dropForeign(['photo_id']); // Eliminar restricción de clave externa
            $table->dropColumn('photo_id');
            $table->dropSoftDeletes(); // Eliminar soft deletes
        });
    }
};
