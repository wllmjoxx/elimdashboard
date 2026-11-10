<?php

namespace App\Modules\Members\Notifications;

use App\Modules\Members\Http\Controllers\SetPasswordController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Registration approved: carries the link where the member creates their
 * password (PRD 4.4).
 */
class RegistrationApproved extends Notification implements ShouldQueue
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

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $days = (int) config('modules.members.password_link_days');

        return (new MailMessage)
            ->subject('Pendaftaran akun GGP Elim disetujui')
            ->greeting("Shalom, {$notifiable->name}!")
            ->line('Pendaftaran Anda sudah disetujui. Selamat bergabung di website GGP Elim.')
            ->line('Buat password Anda lewat tombol di bawah, lalu masuk dengan email ini.')
            ->action('Buat password', SetPasswordController::link($notifiable))
            ->line("Tautan berlaku {$days} hari dan hanya bisa dipakai sekali. Setelah itu, gunakan \"Lupa password\" di halaman masuk.")
            ->salutation('Tuhan Yesus memberkati, GGP Elim');
    }
}
