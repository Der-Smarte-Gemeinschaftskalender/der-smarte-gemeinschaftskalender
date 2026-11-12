<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Frozen copy of the languages and default texts (lang/{de,en}/emailTemplateDefaults.php) at the time
    // of this migration, so later changes to the lang files or the enabled languages can't alter what it inserts.
    private const DEFAULTS = [
        'de' => [
            'VALIDATION' => [
                'subject' => 'Bestätigen Sie Ihre E-Mail-Adresse, um Ihr Konto zu aktivieren',
                'body' => '<h1>Moin</h1><p>Sie haben auf :appName ein Konto registriert, das mit dieser E-Mail-Adresse verknüpft ist. Sie sind nur einen Klick von der Aktivierung entfernt.<br/> Wenn Sie dies nicht angefordert haben, ignorieren Sie diese E-Mail.</p>',
            ],
            'PASSWORD_RESET' => [
                'subject' => 'Passwort zurücksetzen',
                'body' => '<p>Sie haben eine Anfrage zum Zurücksetzen Ihres Passworts bei <span class="highlight">:appName</span> gestellt.</p><br/><p>Bitte klicken Sie auf den folgenden Button, um Ihr Passwort zurückzusetzen:</p>',
            ],
            'APPROVAL_REQUEST_SENT' => [
                'subject' => 'Ihre Genehmigungsanfrage wurde eingereicht: :requestTypeName',
                'body' => '<p>Ihre Anfrage für :requestTypeName wurde erfolgreich eingereicht.</p>',
            ],
            'APPROVAL_REQUEST_HANDLED' => [
                'subject' => 'Ihre Genehmigungsanfrage wurde bearbeitet: :handledStatus',
                'body' => '<p>Ihre Genehmigungsanfrage für :requestTypeName wurde bearbeitet.</p>',
            ],
            'ORGANISATION_INVITATION' => [
                'subject' => 'Einladung zur Organisation',
                'body' => '<p>Sie haben eine Einladung für die Organisation ":organisationName" bekommen.</p>',
            ],
            'ORGANISATION_REMOVAL' => [
                'subject' => 'Entfernung aus Organisation',
                'body' => '<p>Sie wurden aus der folgenden Organisation ":organisationName" entfernt.</p>',
            ],
        ],
        'en' => [
            'VALIDATION' => [
                'subject' => 'Confirm your email address to activate your account',
                'body' => '<h1>Hello</h1><p>You have registered an account on :appName that is linked to this email address. You are just one click away from activating it.<br/> If you did not request this, please ignore this email.</p>',
            ],
            'PASSWORD_RESET' => [
                'subject' => 'Reset password',
                'body' => '<p>You have requested to reset your password on <span class="highlight">:appName</span>.</p><br/><p>Please click the following button to reset your password:</p>',
            ],
            'APPROVAL_REQUEST_SENT' => [
                'subject' => 'Your approval request has been submitted: :requestTypeName',
                'body' => '<p>Your request for :requestTypeName has been submitted successfully.</p>',
            ],
            'APPROVAL_REQUEST_HANDLED' => [
                'subject' => 'Your approval request has been processed: :handledStatus',
                'body' => '<p>Your approval request for :requestTypeName has been processed.</p>',
            ],
            'ORGANISATION_INVITATION' => [
                'subject' => 'Invitation to an organisation',
                'body' => '<p>You have been invited to the organisation ":organisationName".</p>',
            ],
            'ORGANISATION_REMOVAL' => [
                'subject' => 'Removed from organisation',
                'body' => '<p>You have been removed from the organisation ":organisationName".</p>',
            ],
        ],
    ];

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
        foreach (self::DEFAULTS as $locale => $templates) {
            foreach ($templates as $onEvent => $template) {
                $exists = DB::table('email_templates')
                    ->where('on_event', $onEvent)
                    ->where('locale', $locale)
                    ->exists();

                if (!$exists) {
                    DB::table('email_templates')->insert([
                        'on_event' => $onEvent,
                        'locale' => $locale,
                        'subject' => $template['subject'],
                        'body' => $template['body'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
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
        DB::table('email_templates')->where('locale', '!=', 'de')->delete();

        Schema::table('email_templates', function (Blueprint $table) {
            $table->dropUnique(['on_event', 'locale']);
            $table->dropColumn('locale');
        });
    }
};
