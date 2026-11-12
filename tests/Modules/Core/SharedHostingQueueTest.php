<?php

namespace Tests\Modules\Core;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

/**
 * cPanel hosting has no Supervisor: the scheduler cron starts a short-lived
 * queue worker every minute when QUEUE_WORK_VIA_SCHEDULER is on.
 */
class SharedHostingQueueTest extends TestCase
{
    public function test_no_worker_is_scheduled_by_default(): void
    {
        $this->assertNull($this->queueWorker(false));
    }

    public function test_scheduler_runs_a_worker_that_stops_when_the_queue_is_empty(): void
    {
        $worker = $this->queueWorker(true);

        $this->assertNotNull($worker);
        $this->assertSame('* * * * *', $worker->expression);
        $this->assertStringContainsString('--stop-when-empty', (string) $worker->command);
        $this->assertStringContainsString('--max-time=50', (string) $worker->command);
        $this->assertTrue($worker->withoutOverlapping);
    }

    private function queueWorker(bool $enabled): ?Event
    {
        config(['modules.core.queue_via_scheduler' => $enabled]);
        $this->app->forgetInstance(Schedule::class);

        return collect(app(Schedule::class)->events())
            ->first(fn (Event $event) => str_contains((string) $event->command, 'queue:work'));
    }
}
