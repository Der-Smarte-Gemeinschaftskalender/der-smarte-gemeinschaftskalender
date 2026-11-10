<?php

declare(strict_types=1);

/*
 * Default texts of the admin-editable email templates (see App\Enums\EmailTemplateType).
 * They are only used to seed the email_templates table and as a fallback when a template row is missing.
 * Placeholders like :appName are replaced when the email is sent.
 */
return [
    'VALIDATION' => [
        'subject' => 'Confirm your email address to activate your account',
        'body'    => '<h1>Hello</h1><p>You have registered an account on :appName that is linked to this email address. You are just one click away from activating it.<br/> If you did not request this, please ignore this email.</p>',
    ],
    'PASSWORD_RESET' => [
        'subject' => 'Reset password',
        'body'    => '<p>You have requested to reset your password on <span class="highlight">:appName</span>.</p><br/><p>Please click the following button to reset your password:</p>',
    ],
    'APPROVAL_REQUEST_SENT' => [
        'subject' => 'Your approval request has been submitted: :requestTypeName',
        'body'    => '<p>Your request for :requestTypeName has been submitted successfully.</p>',
    ],
    'APPROVAL_REQUEST_HANDLED' => [
        'subject' => 'Your approval request has been processed: :handledStatus',
        'body'    => '<p>Your approval request for :requestTypeName has been processed.</p>',
    ],
    'ORGANISATION_INVITATION' => [
        'subject' => 'Invitation to an organisation',
        'body'    => '<p>You have been invited to the organisation ":organisationName".</p>',
    ],
    'ORGANISATION_REMOVAL' => [
        'subject' => 'Removed from organisation',
        'body'    => '<p>You have been removed from the organisation ":organisationName".</p>',
    ],
];
