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
        Schema::table('business_schedules', function (Blueprint $table) {
            $table->json('time_slots')->nullable()->after('day_of_week');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_schedules', function (Blueprint $table) {
            $table->dropColumn('time_slots');
        });
    }
};
