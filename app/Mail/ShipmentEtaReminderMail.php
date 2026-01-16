<?php

namespace App\Mail;

use App\Models\Shipment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ShipmentEtaReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Shipment $shipment,
        public int $daysBeforeEta
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->daysBeforeEta) {
            3 => 'Paket Anda Akan Tiba dalam 3 Hari',
            2 => 'Paket Anda Akan Tiba dalam 2 Hari',
            1 => 'Paket Anda Akan Tiba Besok',
            0 => 'Paket Anda Tiba Hari Ini',
            default => 'Informasi Pengiriman Paket',
        };

        return new Envelope(
            subject: $subject . ' - ' . $this->shipment->code,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.shipment-eta-reminder',
            with: [
                'shipment' => $this->shipment,
                'daysBeforeEta' => $this->daysBeforeEta,
            ],
        );
    }
}
