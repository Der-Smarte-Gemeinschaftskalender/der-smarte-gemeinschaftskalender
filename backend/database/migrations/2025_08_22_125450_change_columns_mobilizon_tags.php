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
        Schema::table('mobilizon_tags', function (Blueprint $table) {
            $table->dropColumn('slug');
            $table->string('title')->change();
            $table->integer('mobilizon_group_id')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mobilizon_tags', function (Blueprint $table) {
            $table->string('slug')->unique();
            $table->string('title')->nullable();
            $table->integer('mobilizon_group_id')->nullable();
        });
    }
};
