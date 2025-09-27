<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OTPVerificationNotification extends Notification
{
    use Queueable;

    protected string $otp;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $otp)
    {
        $this->otp = $otp;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $userName = $this->getUserName($notifiable);

        return (new MailMessage)
            ->subject('Your Verification Code - Kamotech')
            ->view('emails.otp-verification', [
                'user' => $notifiable,
                'userName' => $userName,
                'otp' => $this->otp,
            ]);
    }

    /**
     * Get the user's display name.
     */
    protected function getUserName($notifiable): string
    {
        if (! empty($notifiable->first_name)) {
            return $notifiable->first_name;
        }
        if (! empty($notifiable->name)) {
            return explode(' ', $notifiable->name)[0];
        }

        return 'Valued Customer';
    }
}
