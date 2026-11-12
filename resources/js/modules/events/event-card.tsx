import { Link } from '@inertiajs/react';
import { CalendarDays, MapPin } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { PublicEvent } from './types';

/**
 * One event as a card: poster (or a grainy band), state, date, time and
 * place. Used on /kegiatan and in the homepage segment.
 */
export function EventCard({
    event,
    className,
}: {
    event: PublicEvent;
    className?: string;
}) {
    return (
        <Link
            href={event.url}
            className={cn(
                'group flex h-full flex-col overflow-hidden rounded-2xl border border-foreground/10 bg-card transition-colors hover:border-foreground/30 focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none',
                className,
            )}
        >
            <div className="relative aspect-[16/9] overflow-hidden">
                {event.imageUrl ? (
                    <img
                        src={event.imageUrl}
                        alt=""
                        loading="lazy"
                        decoding="async"
                        className="size-full object-cover transition-transform duration-300 ease-out group-hover:scale-105"
                    />
                ) : (
                    <div
                        aria-hidden="true"
                        className="grain relative size-full bg-ember"
                    />
                )}
                <span
                    className={cn(
                        'absolute top-3 left-3 rounded-lg glass-float px-2.5 py-1 font-condensed text-[11px] tracking-[0.2em] text-band-fg uppercase',
                        event.state === 'ongoing' &&
                            'border-transparent bg-[#e5241b] text-white',
                    )}
                >
                    {event.stateLabel}
                </span>
            </div>
            <div className="flex flex-1 flex-col p-5">
                <p className="font-condensed text-xs tracking-[0.2em] text-flame-strong uppercase">
                    {event.dateLabel}
                </p>
                <h3 className="mt-2 font-display text-xl leading-tight font-extrabold tracking-tight text-balance group-hover:underline sm:text-2xl">
                    {event.title}
                </h3>
                {event.summary && (
                    <p className="mt-2 line-clamp-2 text-muted-foreground">
                        {event.summary}
                    </p>
                )}
                <div className="mt-auto flex flex-wrap gap-x-4 gap-y-1 pt-4 text-sm text-muted-foreground">
                    <span className="inline-flex items-center gap-1.5">
                        <CalendarDays className="size-4" />
                        {event.timeLabel}
                    </span>
                    {event.location && (
                        <span className="inline-flex items-center gap-1.5">
                            <MapPin className="size-4" />
                            {event.location}
                        </span>
                    )}
                </div>
            </div>
        </Link>
    );
}
