import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { EventCard } from '@/modules/events/event-card';
import { SectionShell, sectionIds } from '../components';
import type { EventsContent } from '../types';

/**
 * Homepage segment: events running now (first) and the next ones. Hidden
 * when there is nothing to show.
 */
export function EventsSection({ content }: { content: EventsContent }) {
    if (content.items.length === 0) {
        return null;
    }

    return (
        <SectionShell id={sectionIds.events}>
            <div className="mb-10 flex flex-wrap items-end justify-between gap-4">
                <div className="max-w-3xl">
                    <p className="mb-3 font-condensed text-sm tracking-[0.3em] text-muted-foreground uppercase">
                        Agenda
                    </p>
                    <h2 className="font-display text-4xl leading-[0.95] font-black tracking-tight text-balance sm:text-6xl">
                        {content.title}
                    </h2>
                    {content.subtitle && (
                        <p className="mt-4 text-lg text-muted-foreground">
                            {content.subtitle}
                        </p>
                    )}
                </div>
                <Link
                    href={content.url}
                    className="inline-flex items-center gap-2 border border-foreground/40 px-5 py-3 text-[13px] font-semibold tracking-[0.12em] uppercase transition-colors hover:border-foreground hover:bg-foreground/5"
                >
                    Semua kegiatan
                    <ArrowRight className="size-4" />
                </Link>
            </div>
            <ul className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                {content.items.map((event) => (
                    <li key={event.id}>
                        <EventCard event={event} />
                    </li>
                ))}
            </ul>
        </SectionShell>
    );
}
