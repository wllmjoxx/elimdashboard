<?php

namespace App\Modules\Members\Notifications;

use App\Modules\Members\Http\Controllers\SetPasswordController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Invitation to a staff account created by Admin or Superadmin (PRD 4.4:
 * "akun staf dibuat atau diundang lewat email").
 */
class StaffInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Retry a few times so a passing mail-server hiccup does not lose the
     * email (overrides the worker's --tries).
     */
    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [10, 60];

    public function __construct(public readonly string $roleLabel, public readonly string $invitedBy) {}

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
            ->subject('Undangan akun website GGP Elim')
            ->greeting("Shalom, {$notifiable->name}!")
            ->line("{$this->invitedBy} mengundang Anda ke website GGP Elim sebagai {$this->roleLabel}.")
            ->line('Buat password Anda lewat tombol di bawah, lalu masuk dengan email ini.')
            ->action('Buat password', SetPasswordController::link($notifiable))
            ->line("Tautan berlaku {$days} hari dan hanya bisa dipakai sekali. Anda juga bisa masuk dengan kode email.")
            ->salutation('Tuhan Yesus memberkati, GGP Elim');
    }
}
