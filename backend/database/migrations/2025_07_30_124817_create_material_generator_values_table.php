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
        Schema::create('material_generator_values', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->integer('mobilizon_group_id')->nullable();
            $table->integer('mobilizon_preferredusername')->nullable();
            $table->json('default_text_settings')->nullable();
            $table->json('default_header_settings')->nullable();
            $table->string('default_dimension')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_generator_values');
    }
};
