<?php

namespace App\Mail;

use App;
use Carbon\Carbon;
use Hash;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendEmailNotifcation extends Mailable
{
    use Queueable, SerializesModels;

    public $events;
    public $intervall;
    public $notificationId;
    public $unsubscribePath;
    public $editPath;

    /**
     * Create a new message instance.
     */
    public function __construct($notifcationId, $token, $events, $intervall)
    {
        $this->events = $events;
        $this->intervall = $intervall;
        $this->notificationId = $notifcationId;
        $this->unsubscribePath = '/exports/email/unsubscribe/' . $token;
        $this->editPath = '/exports/email/edit/' . $token;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Benachrichtigung Termine',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.notificationMail',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
