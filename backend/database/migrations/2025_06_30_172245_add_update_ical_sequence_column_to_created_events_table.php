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
            $table->integer('ical_update_sequence')->nullable()->default(null)->after('filename');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('created_events', function (Blueprint $table) {
            $table->dropColumn('ical_update_sequence');
        });
    }
};
