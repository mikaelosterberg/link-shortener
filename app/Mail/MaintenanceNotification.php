<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MaintenanceNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $message,
        public Carbon $scheduledTime,
        public array $additionalData = [],
        public string $groupName = ''
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🔧 Scheduled Maintenance Notification',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.notifications.maintenance',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
