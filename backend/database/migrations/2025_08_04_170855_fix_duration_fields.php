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
        // change type of duration fields to string
        Schema::table('series_events', function (Blueprint $table) {
            $table->string('duration')->change();
        });
        Schema::table('created_events', function (Blueprint $table) {
            $table->string('duration')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('series_events', function (Blueprint $table) {
            $table->integer('duration')->change();
        });
        Schema::table('created_events', function (Blueprint $table) {
            $table->integer('duration')->change();
        });
    }
};
