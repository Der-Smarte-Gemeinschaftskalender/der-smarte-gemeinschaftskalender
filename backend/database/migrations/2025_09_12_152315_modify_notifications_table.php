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
           // not nullable and rename column
            $table->renameColumn('verification_token', 'token');
            $table->string('token')->nullable(false)->change();
            $table->boolean('is_verified')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->renameColumn('token', 'verification_token');
            $table->string('verification_token')->nullable()->change();
            $table->dropColumn('is_verified');
        });
    }
};
