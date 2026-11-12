<?php

declare(strict_types=1);

return [
    'general' => [
        'unexpectedError'            => 'Unerwarteter Fehler',
        'unexpectedErrorWithMessage' => 'Unerwarteter Fehler: :message',
        'unknownError'               => 'Unbekannter Fehler',
        'groupIdRequired'            => 'Die Mobilizon-Gruppen-ID ist erforderlich.',
        'notInGroup'                 => 'Sie sind nicht Mitglied der Gruppe, oder die Gruppe existiert nicht.',
        'imageNotFound'              => 'Bild nicht gefunden',
        'imageFetchFailed'           => 'Bild konnte nicht abgerufen werden',
    ],

    'auth' => [
        'unauthorized'                      => 'Nicht autorisiert',
        'userValidationFailed'              => 'Nutzervalidierung fehlgeschlagen',
        'registrationUnavailable'           => 'Die Registrierung ist zurzeit leider nicht möglich. Bitte versuchen Sie es später erneut.',
        'strictModeOnly'                    => 'Nur im strikten Modus verfügbar',
        'profileValidationFailed'           => 'Profilvalidierung fehlgeschlagen',
        'organisationValidationFailed'      => 'Organisationsvalidierung fehlgeschlagen',
        'organisationUsernameEqualsProfile' => 'Der Organisations-Benutzername darf nicht mit dem Profil-Benutzernamen übereinstimmen',
        'organisationUsernameTaken'         => 'Organisations-Benutzername bereits vorhanden',
        'mobilizonLoginFailed'              => 'Mobilizon-Anmeldung des Nutzers fehlgeschlagen',
        'profileCreationFailed'             => 'Profilerstellung fehlgeschlagen: :message',
        'personNotAvailable'                => 'Mobilizon-Profil nach der Erstellung nicht verfügbar',
        'invalidEmail'                      => 'Bitte geben Sie eine gültige E-Mail-Adresse ein',
        'resetLinkSent'                     => 'Wenn diese E-Mail-Adresse in unserem System vorhanden ist, erhalten Sie eine E-Mail mit einem Link zum Zurücksetzen Ihres Passworts.',
        'resetLinkFailed'                   => 'Fehler beim Senden des Passwort-Zurücksetzen-Links',
        'userValidated'                     => 'Nutzer erfolgreich validiert',
        'invalidInput'                      => 'Fehlende oder ungültige Eingaben',
        'invalidToken'                      => 'Ungültiges oder abgelaufenes Token',
        'passwordReset'                     => 'Passwort erfolgreich zurückgesetzt',
        'passwordResetFailed'               => 'Fehler beim Zurücksetzen des Passworts',
        'loggedOut'                         => 'Erfolgreich abgemeldet',
    ],

    'events' => [
        'notFound'             => 'Termin nicht gefunden',
        'imageSaveFailed'      => 'Das Bild konnte nicht gespeichert werden. Die Datei ist möglicherweise beschädigt oder liegt in einem Format vor, das nicht unterstützt wird. Der Termin wurde nicht angelegt - bitte wählen Sie ein anderes Bild (JPG, PNG, GIF oder WEBP).',
        'imageTooLarge'        => 'Das Bild überschreitet die maximal erlaubte Dateigröße von :size. Bitte wählen Sie ein kleineres Bild.',
        'imageUploadFailed'    => 'Das Bild konnte nicht hochgeladen werden. Bitte versuchen Sie es erneut.',
        'createFailed'         => 'Termin konnte nicht erstellt werden',
        'deleted'              => 'Termin erfolgreich gelöscht',
        'deleteFailed'         => 'Fehler beim Löschen der Veranstaltung mit Mobilizon ID :id: :error',
    ],

    'series' => [
        'invalidInterval'     => 'Ungültiges Intervall.',
        'noEventCreated'      => 'Es wurde kein Termin erstellt. Bitte überprüfen Sie Ihre Angaben zum Intervall.',
        'allEventsOnHolidays' => 'Es wurden keine Termine erstellt. Alle Termine fallen auf Feiertage oder in Schulferien.',
        'imageSaveFailed'     => 'Das Bild konnte nicht gespeichert werden. Die Datei ist möglicherweise beschädigt oder liegt in einem Format vor, das nicht unterstützt wird. Der Serientermin wurde nicht angelegt - bitte wählen Sie ein anderes Bild (JPG, PNG, GIF oder WEBP).',
        'holidaysFetchFailed' => 'Fehler beim Abrufen der Feiertage. Bitte versuchen Sie es später erneut.',
        'deleted'             => 'Alle Veranstaltungen der Serie wurden erfolgreich gelöscht.',
    ],

    'uploads' => [
        'noData'           => 'Keine Daten hochgeladen. Bitte laden Sie eine gültige ICS-Datei hoch.',
        'noEventsSelected' => 'Keine Termine ausgewählt oder bereits erstellt.',
        'eventsCreated'    => 'Termine erfolgreich erstellt.',
        'deleted'          => 'Hochgeladene Datei und zugehörige Termine wurden erfolgreich gelöscht.',
        'icalParseError'   => 'Fehler beim Verarbeiten der iCal-Daten: :message',
    ],

    'imports' => [
        'invalidMobilizonFields' => 'Ungültige Mobilizon-Felder',
        'urlRequired'            => 'URL ist erforderlich',
        'deleted'                => 'Erfolgreich gelöscht.',
    ],

    'organisations' => [
        'usernameTaken'           => 'Es kann keine Organisation mit diesem Benutzernamen erstellt werden, da dieser bereits vergeben ist.',
        'usernameUsedByUser'      => 'Es kann keine Organisation mit diesem Benutzernamen erstellt werden, da dieser bereits von einem Benutzer verwendet wird.',
        'mobilizonCreateFailed'   => 'Die Organisation konnte nicht in Mobilizon erstellt werden.',
        'updateFailed'            => 'Organisation konnte nicht aktualisiert werden',
        'inviterNotFound'         => 'Einladender Nutzer nicht gefunden',
        'acceptInvitationFailed'  => 'Einladung konnte in Mobilizon nicht angenommen werden',
        'removeMemberFailed'      => 'Fehler beim Entfernen des Mitglieds aus der Organisation',
        'removeMemberMailFailed'  => 'Mitglied wurde zwar aus der Organisation entfernt, aber es gab ein Problem beim Senden der Benachrichtigungs-E-Mail.',
        'memberRemoved'           => 'Mitglied erfolgreich aus der Organisation entfernt',
        'leaveFailed'             => 'Fehler beim Verlassen der Organisation',
        'left'                    => 'Erfolgreich aus der Organisation ausgetreten',
        'recommendedUpdated'      => 'Erfolgreich aktualisiert',
        'recommendedUpdateFailed' => 'Fehler beim Aktualisieren des empfohlenen Status',
        'notFound'                => 'Organisation nicht gefunden',
    ],

    'users' => [
        'personRegistered'     => 'Profil erfolgreich registriert',
        'fillAllFields'        => 'Bitte alle Felder ausfüllen',
        'emailTaken'           => 'Diese E-Mail-Adresse ist bereits vergeben',
        'profileUpdated'       => 'Profil erfolgreich aktualisiert',
        'profileUpdateFailed'  => 'Fehler beim Aktualisieren des Profils: :message',
        'passwordsMismatch'    => 'Die neuen Passwörter stimmen nicht überein',
        'currentPasswordWrong' => 'Aktuelles Passwort ist falsch',
        'passwordUpdated'      => 'Passwort erfolgreich aktualisiert',
    ],

    'admin' => [
        'accessDenied'         => 'Zugriff verweigert. Admin-Rechte erforderlich.',
        'userValidationFailed' => 'Validierung des neuen Benutzers fehlgeschlagen',
        'roleChangeFailed'     => 'Rollenänderung des neuen Benutzers fehlgeschlagen',
        'userCreated'          => 'Erfolgreich erstellt',
        'userUpdated'          => 'Nutzer erfolgreich aktualisiert',
        'userDeleted'          => 'Nutzer erfolgreich gelöscht',
        'userDeleteFailed'     => 'Fehler beim Löschen des Benutzers',
    ],

    'approval' => [
        'fileNotFound'              => 'Datei nicht gefunden',
        'fileUrlNotFound'           => 'Datei-URL nicht gefunden',
        'externalFileFetchFailed'   => 'Fehler beim Abrufen der externen Datei',
        'alreadyHandled'            => 'Anfrage wurde bereits bearbeitet',
        'approved'                  => 'Anfrage erfolgreich genehmigt',
        'approveFailed'             => 'Fehler bei der Genehmigung der Anfrage',
        'approveError'              => 'Anfrage konnte nicht genehmigt werden',
        'rejected'                  => 'Anfrage erfolgreich abgelehnt',
        'rejectFailed'              => 'Fehler beim Ablehnen der Anfrage',
        'invalidTypeOrMethod'       => 'Ungültiger Anfragetyp oder ungültige Methode',
        'createdEventIdMissing'     => 'Termin-ID nicht in den Anfragedaten gefunden',
        'unsupportedType'           => 'Nicht unterstützter Anfragetyp',
        'organisationProcessFailed' => 'Organisation konnte nicht verarbeitet werden',
        'unknownEventType'          => 'Unbekannter Ereignistyp für Genehmigungsanforderung: :type',
        'pendingExistsOrganisation' => 'Es gibt bereits eine ausstehende Anfrage für diese Organisation.',
        'pendingExistsEvent'        => 'Es gibt bereits eine ausstehende Anfrage für diesen Termin.',
        'requested'                 => 'Genehmigung angefordert für :type :action',
        'createFailed'              => 'Fehler beim Erstellen der Genehmigungsanforderung.',
    ],

    'notifications' => [
        'saveFailed'              => 'Fehler beim Speichern der Benachrichtigung.',
        'createFailed'            => 'Fehler beim Erstellen der Benachrichtigung.',
        'subscribed'              => 'Erfolgreich abonniert.',
        'tokenRequired'           => 'Token ist erforderlich',
        'noSubscription'          => 'Diese E-Mail hat kein Abonnement.',
        'updated'                 => 'Benachrichtigung erfolgreich aktualisiert.',
        'updateFailed'            => 'Fehler beim Aktualisieren der Benachrichtigung.',
        'tokenNotFound'           => 'Verifizierungstoken nicht gefunden.',
        'confirmed'               => 'Benachrichtigung erfolgreich bestätigt.',
        'confirmFailed'           => 'Fehler bei der Bestätigung der Benachrichtigung.',
        'declined'                => 'Benachrichtigung erfolgreich abgelehnt.',
        'declineFailed'           => 'Fehler beim Ablehnen der Benachrichtigung.',
        'unsubscribed'            => 'Erfolgreich abgemeldet.',
        'unsubscribeFailed'       => 'Fehler beim Abmelden.',
        'subscriptionFound'       => 'Abonnement gefunden.',
        'subscriptionFetchFailed' => 'Fehler beim Abrufen des Abonnements.',
    ],

    'tiles' => [
        'invalidCoordinates' => 'Ungültige Koordinaten',
        'serverConfigError'  => 'Serverkonfigurationsfehler',
        'fetchFailed'        => 'Fehler beim Abrufen der Kachel',
    ],

    'emailTemplates' => [
        'notFound' => 'Vorlage nicht gefunden',
        'labels'   => [
            'VALIDATION'               => 'E-Mail-Validierung',
            'PASSWORD_RESET'           => 'Passwort zurücksetzen',
            'APPROVAL_REQUEST_SENT'    => 'Genehmigungsanfrage gesendet',
            'APPROVAL_REQUEST_HANDLED' => 'Genehmigungsanfrage bearbeitet',
            'ORGANISATION_INVITATION'  => 'Einladung zur Organisation',
            'ORGANISATION_REMOVAL'     => 'Aus der Organisation entfernt',
        ],
    ],

    'materialGenerator' => [
        'templateDeleted' => 'Vorlage wurde gelöscht',
        'groupRequired'   => 'mobilizon_group_id oder mobilizon_preferredusername sind erforderlich.',
    ],

    'mobilizon' => [
        'invalidResponse' => 'Mobilizon lieferte eine ungültige Antwort (HTTP :status)',
        'tooManyRequests' => 'Zu viele Anfragen - maximale Anzahl an Wiederholungsversuchen überschritten',
    ],
];
