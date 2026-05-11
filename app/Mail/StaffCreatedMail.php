<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StaffCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $staffName,
        public string $staffEmail,
        public string $staffPassword,
        public string $ownerName,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🎉 Chào mừng bạn - Tài khoản nhân viên đã được tạo',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.staff_created',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
