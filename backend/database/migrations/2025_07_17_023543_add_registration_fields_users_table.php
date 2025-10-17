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
        Schema::table('users', function (Blueprint $table) {
            $table->string('mobilizon_email')->nullable();
            $table->string('mobilizon_password')->nullable();
            $table->string('mobilizon_access_token')->nullable();
            $table->dropColumn('mobilizon_key');
        });
        Schema::dropIfExists('mobilizon_logins');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('mobilizon_email');
            $table->dropColumn('mobilizon_password');
            $table->dropColumn('mobilizon_access_token');
            $table->string('mobilizon_key')->nullable();
        });
    }
};
