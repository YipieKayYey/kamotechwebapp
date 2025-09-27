<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

class CustomVerifyEmail extends VerifyEmail
{
    /**
     * The notifiable instance for email customization.
     */
    protected $notifiable;

    /**
     * Build the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $this->notifiable = $notifiable;
        $verificationUrl = $this->verificationUrl($notifiable);

        return $this->buildMailMessage($verificationUrl);
    }

    /**
     * Get the verification URL for the given notifiable.
     */
    protected function verificationUrl($notifiable): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60), // URL expires in 60 minutes
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }

    /**
     * Build the mail message with Kamotech theme.
     */
    protected function buildMailMessage($url): MailMessage
    {
        $userName = $this->getUserName($this->notifiable);

        return (new MailMessage)
            ->subject('Verify Your Email - Kamotech')
            ->view('emails.verify-email-kamotech', [
                'user' => $this->notifiable,
                'userName' => $userName,
                'verificationUrl' => $url,
            ]);
    }

    /**
     * Get the user's name for the email.
     */
    protected function getUserName($notifiable): string
    {
        // Try to get the user's first name, fallback to full name, then email
        if (! empty($notifiable->first_name)) {
            return $notifiable->first_name;
        }

        if (! empty($notifiable->name)) {
            return explode(' ', $notifiable->name)[0]; // First part of name
        }

        return 'Valued Customer'; // Final fallback
    }
}
