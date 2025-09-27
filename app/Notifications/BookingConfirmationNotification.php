<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class BookingConfirmationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public array $bookingSummary,
        public array $promotions = [],
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Thank you for booking with Kamotech!')
            ->view('emails.booking-confirmation-kamotech', [
                'user' => $notifiable,
                'summary' => $this->bookingSummary,
                'promotions' => $this->promotions,
            ]);
    }
}


