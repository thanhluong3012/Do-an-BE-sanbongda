<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OwnerRegistrationRejectedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $ownerName,
        public ?string $businessName = null,
        public string $rejectedReason = '',
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Yêu cầu đăng ký chủ sân bị từ chối',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.owner_registration_rejected',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
