<?php

namespace App\Notifications;

use App\Enums\EmailTemplateType;
use App\Mail\Concerns\RendersEmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomResetPassword extends Notification
{
    use Queueable, RendersEmailTemplate;

    public $token;

    /**
     * Create a new notification instance.
     */
    public function __construct($token)
    {
        $this->token = $token;
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
     * Runs in the user's preferred locale (User::preferredLocale()).
     */
    public function toMail(object $notifiable): MailMessage
    {
        $template = $this->renderEmailTemplate(EmailTemplateType::PASSWORD_RESET);

        return (new MailMessage)
            ->subject($template['subject'])
            ->view('emails.resetPasswordMail', [
                'resetToken' => $this->token,
                'mailBody' => $template['body'],
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
