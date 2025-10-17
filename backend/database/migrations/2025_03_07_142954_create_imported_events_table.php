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
        Schema::create('imported_events', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('url')->nullable();
            $table->boolean('is_active')->default(false);
            $table->integer('user_id');
            $table->integer('mobilizon_group_id');
            $table->json('mobilizon_fields');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imported_events');
    }
};
