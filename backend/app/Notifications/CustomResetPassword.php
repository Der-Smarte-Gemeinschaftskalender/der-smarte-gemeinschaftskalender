<?php

namespace App\Notifications;

use App\Enums\EmailTemplateType;
use App\Http\Controllers\EmailTemplateController;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomResetPassword extends Notification
{
    use Queueable;

    public $token;
    public string $mailBody;
    public string $mailSubject;

    /**
     * Create a new notification instance.
     */

    public function __construct($token)
    {
        $templateController = new EmailTemplateController();
        $this->mailBody = $templateController->getEventDefaultTemplateBody(EmailTemplateType::PASSWORD_RESET);
        $this->mailSubject = $templateController->getEventDefaultTemplateSubject(EmailTemplateType::PASSWORD_RESET);

        $this->token = $token;
        $variables = [
            ':appName' => config('app.name', 'Der Smarte Gemeinschaftskalender'),
        ];
        
        $this->mailBody = str_replace(array_keys($variables), array_values($variables), $this->mailBody);
        $this->mailSubject = str_replace(array_keys($variables), array_values($variables), $this->mailSubject);
    }


    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->mailSubject)
            ->view('emails.resetPasswordMail', [
                'resetToken' => $this->token,
                'mailBody' => $this->mailBody,
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
