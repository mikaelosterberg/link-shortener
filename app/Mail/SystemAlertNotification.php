<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SystemAlertNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $message,
        public string $severity,
        public array $additionalData = [],
        public string $groupName = ''
    ) {}

    public function envelope(): Envelope
    {
        $severityEmoji = match ($this->severity) {
            'high' => '🚨',
            'medium' => '⚠️',
            'low' => 'ℹ️',
            default => '📢',
        };

        return new Envelope(
            subject: $severityEmoji.' System Alert - '.ucfirst($this->severity).' Severity',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.notifications.system-alert',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
