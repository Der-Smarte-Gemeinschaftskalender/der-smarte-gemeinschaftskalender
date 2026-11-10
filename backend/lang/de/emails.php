<?php

declare(strict_types=1);

/*
 * Texts of all emails (resources/views/emails) that are not admin-editable.
 * Emails are rendered in the recipient's language (User::preferredLocale()).
 */
return [
    // Event notification digest: intervals and sections
    'WEEKLY'            => 'Wöchentlich',
    'MONTHLY'           => 'Monatlich',
    'thisWeek'          => 'Diese Woche',
    'nextWeek'          => 'Nächste Woche',
    'followingTwoWeeks' => 'Veranstaltungen in 2-4 Wochen',
    'thisMonth'         => 'Dieser Monat',
    'nextMonth'         => 'Nächster Monat',

    'subjects' => [
        'adminApprovalRequest'             => 'Neue Genehmigungsanfrage: :requestTypeName',
        'adminRequestOrganisationCreation' => 'Neue Organisationsanfrage: ":organisationName"',
        'notification'                     => 'Benachrichtigung Termine',
        'notificationConfirm'              => 'Bestätigen Sie Ihre E-Mail-Adresse, um Benachrichtigungen zu erhalten',
        'userDeleted'                      => 'Löschung Benutzerkonto :appName',
    ],

    // Name of an approval request, e.g. "Einzeltermin erstellen"
    'requestTypeName' => ':type :action',
    'requestTypes'    => [
        'SingleEvent'   => 'Einzeltermin',
        'SeriesEvent'   => 'Serientermin',
        'CreatedEvent'  => 'angelegter Termin',
        'UploadedEvent' => 'hochgeladene Termine',
        'ImportedEvent' => 'importierte Termine',
        'Organisation'  => 'Organisation',
    ],
    'requestActions' => [
        'store'                    => 'erstellen',
        'create'                   => 'erstellen',
        'update'                   => 'aktualisieren',
        'delete'                   => 'löschen',
        'updateGroup'              => 'aktualisieren',
        'changeOrganisationStatus' => 'erstellen',
    ],
    // Value of the :handledStatus template variable
    'handledStatus' => [
        'approved' => 'genehmigt',
        'rejected' => 'abgelehnt',
    ],

    'common' => [
        'greeting'       => 'Moin',
        'alternativeUrl' => 'Alternativ können Sie auch folgende URL im Browser besuchen:',
        'reviewRequest'  => 'Jetzt Anfrage prüfen',
        'confirmEmail'   => 'E-Mail-Adresse bestätigen',
        'requestType'    => 'Anfrageart:',
        'requestId'      => 'Anfrage-ID:',
    ],

    'footer' => [
        'basedOn'             => ':appName basiert auf Mobilizon im Fediverse.',
        'terms'               => 'Nutzungsbedingungen',
        'imprint'             => 'Impressum',
        'privacy'             => 'Datenschutz',
        'unsubscribe'         => 'Wenn Sie keine Benachrichtigungen mehr erhalten möchten, können Sie sich jederzeit :unsubscribeLink. Oder Ihre Einstellungen :editLink.',
        'unsubscribeLinkText' => 'hier abmelden',
        'editLinkText'        => 'hier bearbeiten',
    ],

    'adminApprovalRequest' => [
        'heading'     => 'Neue Genehmigungsanfrage',
        'intro'       => 'Eine neue Genehmigungsanfrage wurde eingereicht:',
        'requestedBy' => 'Angefordert von:',
        'reviewHint'  => 'Bitte überprüfen Sie die Anfrage und genehmigen oder lehnen Sie sie ab.',
    ],

    'adminRequestOrganisationCreation' => [
        'text' => 'Es wurde die Erstellung einer neuen Organisation mit dem Namen ":organisationName" angefragt.',
    ],

    'approvalRequestCreated' => [
        'heading'    => 'Genehmigungsanfrage eingereicht',
        'reviewInfo' => 'Ein Administrator wird Ihre Anfrage überprüfen und Sie werden per E-Mail benachrichtigt, wenn die Anfrage genehmigt oder abgelehnt wird.',
        'thanks'     => 'Vielen Dank für Ihre Geduld!',
    ],

    'approvalRequestHandled' => [
        'heading'        => 'Ihre Genehmigungsanfrage wurde bearbeitet',
        'status'         => 'Status:',
        'statusApproved' => 'Genehmigt',
        'statusRejected' => 'Abgelehnt',
        'adminComment'   => 'Kommentar vom Admin:',
        'approvedText'   => 'Ihre Anfrage wurde genehmigt und die Änderungen wurden vorgenommen.',
        'rejectedText'   => 'Leider wurde Ihre Anfrage abgelehnt. Die Änderungen wurden nicht vorgenommen.',
    ],

    'userDeleted' => [
        'text' => 'Ihr Benutzerkonto auf :appName wurde gelöscht.',
    ],

    'notificationConfirm' => [
        'heading'          => 'Bitte bestätigen Sie Ihre E-Mail-Adresse',
        'subscribed'       => 'Sie haben sich für Veranstaltungsbenachrichtigungen bei :appName angemeldet.',
        'confirmHint'      => 'Bitte :confirm, damit wir Sie regelmäßig über neue Veranstaltungen informieren dürfen.',
        'confirmHighlight' => 'bestätigen Sie Ihre E-Mail-Adresse',
    ],

    'notification' => [
        'heading' => [
            'WEEKLY'  => 'Moin, hier ist Ihre wöchentliche Veranstaltungsübersicht',
            'MONTHLY' => 'Moin, hier ist Ihre monatliche Veranstaltungsübersicht',
        ],
        'intro'             => 'Hier finden Sie die aktuellen Veranstaltungen auf :appName – passend zu Ihren Interessen.',
        'noEvents'          => 'Es wurden keine Termine gefunden.',
        'alternativeEvents' => 'Wir haben alternative Termine rausgesucht, die für Sie interessant sein könnten:',
        'eventFallbackTitle' => 'Veranstaltung',
        'dateTbd'           => 'Datum wird noch bekannt gegeben',
        // PHP date format for the event start (Carbon::translatedFormat)
        'dateFormat'        => 'D d.m.Y H:i \U\h\r',
    ],

    'organisationInvite' => [
        'button' => 'Einladung annehmen oder ablehnen',
    ],

    'organisationRemoved' => [
        'button' => 'Meine Organisationen ansehen',
    ],

    'resetPassword' => [
        'heading'    => 'Passwort zurücksetzen',
        'button'     => 'Passwort zurücksetzen',
        'ignoreHint' => 'Wenn Sie diese Anfrage nicht gestellt haben, ignorieren Sie diese E-Mail bitte.',
    ],
];
