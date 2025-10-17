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
        Schema::table('material_generator_values', function (Blueprint $table) {
            $table->string('mobilizon_preferredusername')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('material_generator_values', function (Blueprint $table) {
            $table->integer('mobilizon_preferredusername')->nullable()->change();
        });
    }
};
