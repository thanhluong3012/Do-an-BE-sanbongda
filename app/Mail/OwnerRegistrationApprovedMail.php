<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OwnerRegistrationApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $ownerName,
        public string $ownerEmail,
        public string $ownerPassword,
        public ?string $businessName = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Yêu cầu đăng ký chủ sân đã được duyệt',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.owner_registration_approved',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
