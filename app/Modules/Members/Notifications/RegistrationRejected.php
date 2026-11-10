<?php

namespace App\Modules\Members\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Registration rejected, with the reason Admin gave (PRD 4.4).
 */
class RegistrationRejected extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Retry a few times so a passing mail-server hiccup does not lose the
     * email (overrides the worker's --tries).
     */
    public int $tries = 3;

    /**
     * Seconds to wait before each retry.
     *
     * @var list<int>
     */
    public array $backoff = [10, 60];

    public function __construct(public readonly string $reason) {}

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
            ->subject('Pendaftaran akun GGP Elim tidak disetujui')
            ->greeting("Shalom, {$notifiable->name}.")
            ->line('Mohon maaf, pendaftaran akun Anda belum dapat kami setujui dengan alasan berikut:')
            ->line($this->reason)
            ->line('Jika ada pertanyaan, silakan hubungi sekretariat GGP Elim.')
            ->salutation('Tuhan Yesus memberkati, GGP Elim');
    }
}
