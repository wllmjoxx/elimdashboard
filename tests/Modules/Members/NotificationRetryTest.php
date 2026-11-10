<?php

namespace Tests\Modules\Members;

use App\Modules\Members\Notifications\RegistrationApproved;
use App\Modules\Members\Notifications\RegistrationNeedsInfo;
use App\Modules\Members\Notifications\RegistrationReceived;
use App\Modules\Members\Notifications\RegistrationRejected;
use Illuminate\Contracts\Queue\ShouldQueue;
use Tests\TestCase;

class NotificationRetryTest extends TestCase
{
    public function test_registration_emails_are_queued_with_retries(): void
    {
        $notifications = [
            new RegistrationReceived,
            new RegistrationApproved,
            new RegistrationRejected('alasan'),
            new RegistrationNeedsInfo('pesan'),
        ];

        foreach ($notifications as $notification) {
            $this->assertInstanceOf(ShouldQueue::class, $notification);
            $this->assertSame(3, $notification->tries);
            $this->assertSame([10, 60], $notification->backoff);
        }
    }
}
