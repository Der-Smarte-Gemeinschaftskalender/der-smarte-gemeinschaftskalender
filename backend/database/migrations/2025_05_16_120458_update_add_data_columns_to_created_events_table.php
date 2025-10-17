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
        Schema::table('created_events', function (Blueprint $table) {
            $table->date('start')->nullable()->default(null);
            $table->string('time')->nullable()->default(null);
            $table->integer('duration')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('created_events', function (Blueprint $table) {
            $table->dropColumn('start');
            $table->dropColumn('time');
            $table->dropColumn('duration');
        });
    }
};
