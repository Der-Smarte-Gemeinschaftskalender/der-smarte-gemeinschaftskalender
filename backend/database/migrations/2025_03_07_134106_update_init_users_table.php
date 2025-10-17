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
            $table->dropColumn('name');
            $table->enum('type', ['admin', 'user'])->default('user')->after('id');
            $table->string('first_name')->nullable()->after('type');
            $table->string('last_name')->nullable()->after('first_name');
            $table->boolean('is_active')->default(false)->after('last_name');
            $table->string('admin_note')->nullable()->after('is_active');
            $table->string('mobilizon_key')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name');
            $table->dropColumn('type');
            $table->dropColumn('first_name');
            $table->dropColumn('last_name');
            $table->dropColumn('is_active');
            $table->dropColumn('admin_note');
            $table->dropColumn('mobilizon_key');
        });
    }
};
