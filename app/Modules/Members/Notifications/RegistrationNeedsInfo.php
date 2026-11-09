<?php

namespace App\Modules\Members\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Admin needs more information before deciding (PRD 4.4). The account
 * stays pending.
 */
class RegistrationNeedsInfo extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $request) {}

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
            ->subject('Mohon lengkapi data pendaftaran GGP Elim')
            ->greeting("Shalom, {$notifiable->name}!")
            ->line('Admin membutuhkan informasi tambahan sebelum menyetujui pendaftaran Anda:')
            ->line($this->request)
            ->line('Silakan balas email ini atau hubungi sekretariat GGP Elim. Pendaftaran Anda tetap tersimpan.')
            ->salutation('Tuhan Yesus memberkati, GGP Elim');
    }
}
