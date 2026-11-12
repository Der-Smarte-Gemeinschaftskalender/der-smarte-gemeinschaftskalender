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
        Schema::table('notifications', function (Blueprint $table) {
            // Language of the newsletter emails: the request language at subscription time (e.g. "de", "en")
            $table->string('locale', 5)->nullable()->after('email');
        });

        // Existing subscriptions were always sent in the instance default language, so they keep it.
        DB::table('notifications')->whereNull('locale')->update(['locale' => config('app.locale')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }
};
