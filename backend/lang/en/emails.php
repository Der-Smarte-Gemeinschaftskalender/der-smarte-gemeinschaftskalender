<?php

declare(strict_types=1);

/*
 * Texts of all emails (resources/views/emails) that are not admin-editable.
 * Emails are rendered in the recipient's language (User::preferredLocale()).
 */
return [
    // Event notification digest: intervals and sections
    'WEEKLY'            => 'Weekly',
    'MONTHLY'           => 'Monthly',
    'thisWeek'          => 'This week',
    'nextWeek'          => 'Next week',
    'followingTwoWeeks' => 'Events in 2-4 weeks',
    'thisMonth'         => 'This month',
    'nextMonth'         => 'Next month',

    'subjects' => [
        'adminApprovalRequest'             => 'New approval request: :requestTypeName',
        'adminRequestOrganisationCreation' => 'New organisation request: ":organisationName"',
        'notification'                     => 'Event notification',
        'notificationConfirm'              => 'Confirm your email address to receive notifications',
        'userDeleted'                      => 'Deletion of your :appName account',
    ],

    // Name of an approval request, e.g. "single event creation"
    'requestTypeName' => ':type :action',
    'requestTypes'    => [
        'SingleEvent'   => 'single event',
        'SeriesEvent'   => 'series event',
        'CreatedEvent'  => 'created event',
        'UploadedEvent' => 'uploaded events',
        'ImportedEvent' => 'imported events',
        'Organisation'  => 'organisation',
    ],
    'requestActions' => [
        'store'                    => 'creation',
        'create'                   => 'creation',
        'update'                   => 'update',
        'delete'                   => 'deletion',
        'updateGroup'              => 'update',
        'changeOrganisationStatus' => 'creation',
    ],
    // Value of the :handledStatus template variable
    'handledStatus' => [
        'approved' => 'approved',
        'rejected' => 'rejected',
    ],

    'common' => [
        'greeting'       => 'Hello',
        'alternativeUrl' => 'Alternatively, you can open the following URL in your browser:',
        'reviewRequest'  => 'Review request now',
        'confirmEmail'   => 'Confirm email address',
        'requestType'    => 'Request type:',
        'requestId'      => 'Request ID:',
    ],

    'footer' => [
        'basedOn'             => ':appName is based on Mobilizon in the Fediverse.',
        'terms'               => 'Terms of use',
        'imprint'             => 'Imprint',
        'privacy'             => 'Privacy policy',
        'unsubscribe'         => 'If you no longer want to receive notifications, you can :unsubscribeLink at any time. Or :editLink.',
        'unsubscribeLinkText' => 'unsubscribe here',
        'editLinkText'        => 'edit your settings here',
    ],

    'adminApprovalRequest' => [
        'heading'     => 'New approval request',
        'intro'       => 'A new approval request has been submitted:',
        'requestedBy' => 'Requested by:',
        'reviewHint'  => 'Please review the request and approve or reject it.',
    ],

    'adminRequestOrganisationCreation' => [
        'text' => 'The creation of a new organisation named ":organisationName" has been requested.',
    ],

    'approvalRequestCreated' => [
        'heading'    => 'Approval request submitted',
        'reviewInfo' => 'An administrator will review your request and you will be notified by email once it has been approved or rejected.',
        'thanks'     => 'Thank you for your patience!',
    ],

    'approvalRequestHandled' => [
        'heading'        => 'Your approval request has been processed',
        'status'         => 'Status:',
        'statusApproved' => 'Approved',
        'statusRejected' => 'Rejected',
        'adminComment'   => 'Comment from the admin:',
        'approvedText'   => 'Your request has been approved and the changes have been applied.',
        'rejectedText'   => 'Unfortunately, your request has been rejected. The changes have not been applied.',
    ],

    'userDeleted' => [
        'text' => 'Your account on :appName has been deleted.',
    ],

    'notificationConfirm' => [
        'heading'          => 'Please confirm your email address',
        'subscribed'       => 'You have subscribed to event notifications on :appName.',
        'confirmHint'      => 'Please :confirm so that we may keep you informed about new events.',
        'confirmHighlight' => 'confirm your email address',
    ],

    'notification' => [
        'heading' => [
            'WEEKLY'  => 'Hello, here is your weekly event overview',
            'MONTHLY' => 'Hello, here is your monthly event overview',
        ],
        'intro'             => 'Here you can find the current events on :appName that match your interests.',
        'noEvents'          => 'No events were found.',
        'alternativeEvents' => 'We have picked out some alternative events that might interest you:',
        'eventFallbackTitle' => 'Event',
        'dateTbd'           => 'Date to be announced',
        // PHP date format for the event start (Carbon::translatedFormat)
        'dateFormat'        => 'D, d/m/Y g:i A',
    ],

    'organisationInvite' => [
        'button' => 'Accept or decline invitation',
    ],

    'organisationRemoved' => [
        'button' => 'View my organisations',
    ],

    'resetPassword' => [
        'heading'    => 'Reset password',
        'button'     => 'Reset password',
        'ignoreHint' => 'If you did not make this request, please ignore this email.',
    ],
];
