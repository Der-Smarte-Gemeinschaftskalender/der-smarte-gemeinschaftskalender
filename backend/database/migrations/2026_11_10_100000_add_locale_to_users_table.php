<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Preferred language for emails sent to this user (e.g. "de", "en")
            $table->string('locale', 5)->nullable()->after('email');
        });

        // Existing users get the instance default language (console: not overwritten per request)
        DB::table('users')->whereNull('locale')->update(['locale' => config('app.locale')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }
};
