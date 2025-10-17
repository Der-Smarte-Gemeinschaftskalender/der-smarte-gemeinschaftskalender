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
        Schema::create('created_events', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('mobilizon_uuid')->nullable();
            $table->integer('series_events_id')->nullable()->default(null);
            $table->integer('imported_events_id')->nullable()->default(null);
            $table->integer('uploaded_events_id')->nullable()->default(null);
            $table->integer('ical_id')->nullable()->default(null);
            $table->integer('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('created_events');
    }
};
