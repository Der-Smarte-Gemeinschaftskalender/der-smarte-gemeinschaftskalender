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
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('organisation_id')->nullable()->change();
            $table->string('location_hash', 10)->nullable()->change();
            $table->string('address')->nullable()->after('location_hash');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('organisation_id')->nullable(false)->change();
            $table->string('location_hash')->nullable()->change();
            $table->dropColumn('address');
        });
    }
};
