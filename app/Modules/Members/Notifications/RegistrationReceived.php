<?php

namespace App\Modules\Members\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent right after registering: the account waits for Admin (PRD 4.4).
 */
class RegistrationReceived extends Notification implements ShouldQueue
{
    use Queueable;

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
            ->subject('Pendaftaran akun GGP Elim diterima')
            ->greeting("Shalom, {$notifiable->name}!")
            ->line('Terima kasih sudah mendaftar di website GGP Elim.')
            ->line('Admin akan meninjau pendaftaran Anda. Kami akan mengirim email lagi setelah ada keputusan.')
            ->salutation('Tuhan Yesus memberkati, GGP Elim');
    }
}
