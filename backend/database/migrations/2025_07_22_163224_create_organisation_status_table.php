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
        Schema::create('organisation_status', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->integer('mobilizon_group_id')->nullable();
            $table->string('status')->nullable();
            $table->string('admin_note')->nullable();
            $table->json('requested_organisation_data')->nullable();
            $table->integer('requested_by_user_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organisation_status');
    }
};
