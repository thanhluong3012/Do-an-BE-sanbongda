<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TournamentRegistrationRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $captainName,
        public string $teamName,
        public string $tournamentName,
        public string $rejectedReason,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '❌ Đăng ký giải đấu bị từ chối - ' . $this->tournamentName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tournament_registration_rejected',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
