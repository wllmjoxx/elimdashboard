<?php

namespace App\Modules\Core\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Six-digit sign-in code (PRD 4.4). Not queued: LoginCodes sends it right
 * after the response so it never waits for a queue worker.
 */
class LoginCode extends Notification
{
    use Queueable;

    public function __construct(public readonly string $code, public readonly int $minutes) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Kode masuk GGP Elim: {$this->code}")
            ->greeting("Shalom, {$notifiable->name}!")
            ->line('Gunakan kode berikut untuk masuk ke website GGP Elim:')
            ->line("**{$this->code}**")
            ->line("Kode berlaku {$this->minutes} menit dan hanya bisa dipakai sekali.")
            ->line('Jika Anda tidak meminta kode ini, abaikan email ini. Jangan berikan kode kepada siapa pun.')
            ->salutation('Tuhan Yesus memberkati, GGP Elim');
    }
}
