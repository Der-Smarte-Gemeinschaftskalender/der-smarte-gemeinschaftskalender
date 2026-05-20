<?php

namespace App\Enums;

enum EmailTemplateType: string
{
    // AUTH
    case VALIDATION = 'VALIDATION';
    case PASSWORD_RESET = 'PASSWORD_RESET';

    // APPROVAL REQUEST
    case APPROVAL_REQUEST_SENT = 'APPROVAL_REQUEST_SENT';
    case APPROVAL_REQUEST_HANDLED = 'APPROVAL_REQUEST_HANDLED';
    
    // ORGANISATION
    case ORGANISATION_INVITATION = 'ORGANISATION_INVITATION';
    case ORGANISATION_REMOVAL = 'ORGANISATION_REMOVAL';

    public function getLabel(): string
    {
        return match ($this) {
            self::VALIDATION => 'E-Mail-Validierung',
            self::PASSWORD_RESET => 'Passwort zurücksetzen',

            self::APPROVAL_REQUEST_SENT => 'Genehmigungsanfrage gesendet',
            self::APPROVAL_REQUEST_HANDLED => 'Genehmigungsanfrage bearbeitet',

            self::ORGANISATION_INVITATION => 'Einladung zur Organisation',
            self::ORGANISATION_REMOVAL => 'Aus der Organisation entfernt',
        };
    }


    public function getSubject(): string
    {
        return match ($this) {
            self::VALIDATION => 'Bestätigen Sie Ihre E-Mail-Adresse, um Ihr Konto zu aktivieren',
            self::PASSWORD_RESET => 'Passwort zurücksetzen',
            
            self::APPROVAL_REQUEST_SENT => 'Ihre Genehmigungsanfrage wurde eingereicht: :requestTypeName',
            self::APPROVAL_REQUEST_HANDLED => 'Ihre Genehmigungsanfrage wurde bearbeitet: :handledStatus',

            self::ORGANISATION_INVITATION => 'Einladung zur Organisation',
            self::ORGANISATION_REMOVAL => 'Entfernung aus Organisation',
        };
    }

    public function getBody(): string
    {
        return match ($this) {
            self::VALIDATION => '<h1>Moin</h1><p>Sie haben auf :appName ein Konto registriert, das mit dieser E-Mail-Adresse verknüpft ist. Sie sind nur einen Klick von der Aktivierung entfernt.<br/> Wenn Sie dies nicht angefordert haben, ignorieren Sie diese E-Mail.</p>',
            self::PASSWORD_RESET => '<p>Sie haben eine Anfrage zum Zurücksetzen Ihres Passworts bei <span class="highlight">:appName</span> gestellt.</p><br/><p>Bitte klicken Sie auf den folgenden Button, um Ihr Passwort zurückzusetzen:</p>',

            self::APPROVAL_REQUEST_SENT => '<p>Ihre Anfrage für :requestTypeName wurde erfolgreich eingereicht.</p>',
            self::APPROVAL_REQUEST_HANDLED => '<p>Ihre Genehmigungsanfrage für :requestTypeName wurde bearbeitet.</p>',

            self::ORGANISATION_INVITATION => '<p>Sie haben eine Einladung für die Organisation ":organisationName" bekommen.</p>',
            self::ORGANISATION_REMOVAL => '<p>Sie wurden aus der folgenden Organisation ":organisationName" entfernt.</p>',
        };
    }
}
