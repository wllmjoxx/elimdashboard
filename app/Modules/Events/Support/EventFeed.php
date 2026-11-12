<?php

namespace App\Modules\Events\Support;

use App\Modules\Events\Models\Event;
use Carbon\CarbonInterface;

/**
 * Turns events into the props the public pages and the homepage render.
 */
class EventFeed
{
    /**
     * Ongoing events first, then the next upcoming ones.
     *
     * @return list<array<string, mixed>>
     */
    public function forHome(?int $limit = null): array
    {
        $limit ??= (int) config('modules.events.home_limit');

        $ongoing = Event::query()->published()->ongoing()->limit($limit)->get();
        $upcoming = Event::query()->published()->upcoming()->limit(max(0, $limit - $ongoing->count()))->get();

        return $ongoing->concat($upcoming)->map(fn (Event $event) => $this->present($event))->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function present(Event $event, bool $withBody = false): array
    {
        $state = $event->state();

        return [
            'id' => $event->id,
            'title' => $event->title,
            'slug' => $event->slug,
            'summary' => $event->summary,
            'location' => $event->location,
            'startsAt' => $event->starts_at->toDateTimeString(),
            'endsAt' => $event->ends_at?->toDateTimeString(),
            'dateLabel' => $this->dateLabel($event->starts_at, $event->ends_at),
            'timeLabel' => $this->timeLabel($event->starts_at, $event->ends_at),
            'state' => $state,
            'stateLabel' => match ($state) {
                Event::ONGOING => 'Sedang berlangsung',
                Event::UPCOMING => 'Akan datang',
                default => 'Sudah lewat',
            },
            'imageUrl' => $event->imageUrl(),
            'registrationUrl' => $event->registration_url,
            'url' => route('events.public.show', $event->slug),
            ...($withBody ? ['body' => $event->body] : []),
        ];
    }

    private function dateLabel(CarbonInterface $start, ?CarbonInterface $end): string
    {
        $from = $start->copy()->locale('id');

        if ($end === null || $end->isSameDay($start)) {
            return $from->translatedFormat('l, j F Y');
        }

        $to = $end->copy()->locale('id');

        return $start->isSameMonth($end)
            ? $from->translatedFormat('j').'–'.$to->translatedFormat('j F Y')
            : $from->translatedFormat('j F').' – '.$to->translatedFormat('j F Y');
    }

    private function timeLabel(CarbonInterface $start, ?CarbonInterface $end): string
    {
        $from = $start->format('H.i');

        return $end !== null && $end->isSameDay($start)
            ? "{$from}–{$end->format('H.i')} WIB"
            : "{$from} WIB";
    }
}
