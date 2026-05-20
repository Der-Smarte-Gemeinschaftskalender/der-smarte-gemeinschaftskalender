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
        Schema::create('material_generator_templates', function (Blueprint $table) {
            $table->id();
            $table->integer('mobilizon_group_id');
            $table->string('name');
            $table->enum('material_type', ['event', 'eventList']);
            $table->string('dimension');
            $table->json('global_settings');
            $table->json('objects_data');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_generator_templates');
    }
};
