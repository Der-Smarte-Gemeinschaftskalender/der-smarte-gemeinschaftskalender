<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Frozen copy of the German defaults as shipped, so later enum/lang changes can't alter what this migration inserts.
    private const TEMPLATES = [
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
    ];

    public function up(): void
    {    
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('on_event')->nullable();
            $table->string('subject', 70); 
            $table->text('body');
            $table->timestamps();
        });

        foreach (self::TEMPLATES as $onEvent => $template) {
            DB::table('email_templates')->insert([
                'on_event' => $onEvent,
                'subject' => $template['subject'],
                'body' => $template['body'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
