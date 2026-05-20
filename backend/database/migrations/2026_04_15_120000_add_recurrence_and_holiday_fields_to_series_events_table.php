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
        Schema::table('series_events', function (Blueprint $table) {
            $table->unsignedTinyInteger('weekly_day')->nullable()->after('intervall');
            $table->unsignedTinyInteger('monthly_week_day')->nullable()->after('weekly_day');
            $table->json('monthly_weeks')->nullable()->after('monthly_week_day');
            $table->boolean('monthly_use_start_date_as_default')->default(false)->after('monthly_weeks');
            $table->boolean('holidays_check')->default(false)->after('monthly_use_start_date_as_default');
            $table->boolean('school_holidays_check')->default(false)->after('holidays_check');
            $table->string('holidays_state')->default('none')->after('school_holidays_check');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('series_events', function (Blueprint $table) {
            $table->dropColumn([
                'weekly_day',
                'monthly_week_day',
                'monthly_weeks',
                'monthly_use_start_date_as_default',
                'holidays_check',
                'school_holidays_check',
                'holidays_state',
            ]);
        });
    }
};
