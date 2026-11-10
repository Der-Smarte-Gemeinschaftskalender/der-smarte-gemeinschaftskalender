<?php

namespace App\Mail\Concerns;

use App\Enums\EmailTemplateType;
use App\Models\EmailTemplate;

/**
 * Renders an admin-editable email template (subject + body) in the current app locale.
 *
 * Call it from envelope()/content()/toMail(), never from the constructor: Laravel switches
 * to the recipient's locale (User::preferredLocale()) only while the email is being built.
 */
trait RendersEmailTemplate
{
    /**
     * @param  array<string, string>  $variables  Placeholder => value, e.g. [':organisationName' => 'Foo']
     * @return array{subject: string, body: string}
     */
    protected function renderEmailTemplate(EmailTemplateType $type, array $variables = []): array
    {
        $template = EmailTemplate::forEvent($type);

        $variables = [
            ':appName' => config('app.name', 'Der Smarte Gemeinschaftskalender'),
            ...$variables,
        ];

        return [
            'subject' => str_replace(array_keys($variables), array_values($variables), $template->subject),
            'body' => str_replace(array_keys($variables), array_values($variables), $template->body),
        ];
    }
}
