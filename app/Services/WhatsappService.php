<?php

namespace App\Services;

class WhatsappService
{
    public function send(string $phone, string $message): void
    {
        logger("WA to {$phone}: {$message}");
    }
}
