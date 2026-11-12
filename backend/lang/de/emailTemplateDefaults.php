<?php

declare(strict_types=1);

/*
 * Default texts of the admin-editable email templates (see App\Enums\EmailTemplateType).
 * They are only used to seed the email_templates table and as a fallback when a template row is missing.
 * Placeholders like :appName are replaced when the email is sent.
 */
return [
    'VALIDATION' => [
        'subject' => 'Bestätigen Sie Ihre E-Mail-Adresse, um Ihr Konto zu aktivieren',
        'body'    => '<h1>Moin</h1><p>Sie haben auf :appName ein Konto registriert, das mit dieser E-Mail-Adresse verknüpft ist. Sie sind nur einen Klick von der Aktivierung entfernt.<br/> Wenn Sie dies nicht angefordert haben, ignorieren Sie diese E-Mail.</p>',
    ],
    'PASSWORD_RESET' => [
        'subject' => 'Passwort zurücksetzen',
        'body'    => '<p>Sie haben eine Anfrage zum Zurücksetzen Ihres Passworts bei <span class="highlight">:appName</span> gestellt.</p><br/><p>Bitte klicken Sie auf den folgenden Button, um Ihr Passwort zurückzusetzen:</p>',
    ],
    'APPROVAL_REQUEST_SENT' => [
        'subject' => 'Ihre Genehmigungsanfrage wurde eingereicht: :requestTypeName',
        'body'    => '<p>Ihre Anfrage für :requestTypeName wurde erfolgreich eingereicht.</p>',
    ],
    'APPROVAL_REQUEST_HANDLED' => [
        'subject' => 'Ihre Genehmigungsanfrage wurde bearbeitet: :handledStatus',
        'body'    => '<p>Ihre Genehmigungsanfrage für :requestTypeName wurde bearbeitet.</p>',
    ],
    'ORGANISATION_INVITATION' => [
        'subject' => 'Einladung zur Organisation',
        'body'    => '<p>Sie haben eine Einladung für die Organisation ":organisationName" bekommen.</p>',
    ],
    'ORGANISATION_REMOVAL' => [
        'subject' => 'Entfernung aus Organisation',
        'body'    => '<p>Sie wurden aus der folgenden Organisation ":organisationName" entfernt.</p>',
    ],
];
