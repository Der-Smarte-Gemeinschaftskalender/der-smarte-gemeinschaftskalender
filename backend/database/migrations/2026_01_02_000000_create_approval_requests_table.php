<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApprovalRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('requestable');
            $table->string('request_type'); // e.g. 'create' or 'edit'
            $table->json('payload');
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->unsignedBigInteger('handled_by_user_id')->nullable();
            $table->text('admin_comment')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('approval_requests');
    }
}
