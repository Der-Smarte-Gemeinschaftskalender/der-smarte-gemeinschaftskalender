<?php

use App\Enums\EmailTemplateType;
use App\Http\Middleware\SetLocale;
use App\Models\EmailTemplate;
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
        // The existing templates contain the German defaults, so they get "de".
        Schema::table('email_templates', function (Blueprint $table) {
            $table->string('locale', 5)->default('de')->after('on_event');
        });

        // Add the missing languages without touching templates an admin already edited.
        foreach (EmailTemplateType::cases() as $type) {
            foreach (SetLocale::SUPPORTED_LOCALES as $locale) {
                EmailTemplate::firstOrCreate(
                    ['on_event' => $type->value, 'locale' => $locale],
                    ['subject' => $type->getSubject($locale), 'body' => $type->getBody($locale)]
                );
            }
        }

        Schema::table('email_templates', function (Blueprint $table) {
            $table->unique(['on_event', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        EmailTemplate::where('locale', '!=', 'de')->delete();

        Schema::table('email_templates', function (Blueprint $table) {
            $table->dropUnique(['on_event', 'locale']);
            $table->dropColumn('locale');
        });
    }
};
