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
        return __('messages.emailTemplates.labels.' . $this->value);
    }


    /**
     * Default subject in the given language (see lang/{locale}/emailTemplateDefaults.php).
     * Placeholders like :appName are kept and replaced when the email is sent.
     */
    public function getSubject(string $locale): string
    {
        return __("emailTemplateDefaults.{$this->value}.subject", [], $locale);
    }

    /**
     * Default HTML body in the given language (see lang/{locale}/emailTemplateDefaults.php).
     * Placeholders like :appName are kept and replaced when the email is sent.
     */
    public function getBody(string $locale): string
    {
        return __("emailTemplateDefaults.{$this->value}.body", [], $locale);
    }
}
