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
        Schema::table('single_events', function (Blueprint $table) {
            $table->dropColumn('description');
        });
        Schema::table('series_events', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('single_events', function (Blueprint $table) {
            $table->string('description')->nullable();
        });
        Schema::table('series_events', function (Blueprint $table) {
            $table->string('description')->nullable();
        });
    }
};
