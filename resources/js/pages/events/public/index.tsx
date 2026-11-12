import { Head } from '@inertiajs/react';
import { EventCard } from '@/modules/events/event-card';
import type { PublicEvent } from '@/modules/events/types';
import { PageHero } from '@/modules/landing/components';
import { nav } from '@/modules/landing/nav';
import { Reveal } from '@/modules/landing/reveal';
import { SiteFooter } from '@/modules/landing/site-footer';

type Props = {
    ongoing: PublicEvent[];
    upcoming: PublicEvent[];
    past: PublicEvent[];
};

function Group({
    id,
    eyebrow,
    title,
    events,
}: {
    id: string;
    eyebrow: string;
    title: string;
    events: PublicEvent[];
}) {
    if (events.length === 0) {
        return null;
    }

    return (
        <section
            aria-labelledby={id}
            className="px-4 py-14 sm:px-6 sm:py-20 lg:px-10"
        >
            <div className="mx-auto max-w-7xl">
                <p className="font-condensed text-sm tracking-[0.3em] text-muted-foreground uppercase">
                    {eyebrow}
                </p>
                <h2
                    id={id}
                    className="mt-3 mb-10 font-display text-4xl leading-[0.95] font-black tracking-tight sm:text-5xl"
                >
                    {title}
                </h2>
                <ul className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    {events.map((event) => (
                        <li key={event.id}>
                            <Reveal className="h-full">
                                <EventCard event={event} />
                            </Reveal>
                        </li>
                    ))}
                </ul>
            </div>
        </section>
    );
}

/** Public "Kegiatan": now, coming, and the latest past events. */
export default function EventsPublicIndex({ ongoing, upcoming, past }: Props) {
    const empty =
        ongoing.length === 0 && upcoming.length === 0 && past.length === 0;

    return (
        <>
            <Head title="Kegiatan" />

            <PageHero
                eyebrow="Kegiatan"
                title="Bertumbuh bersama"
                description="Kegiatan GGP Elim yang sedang berjalan dan akan datang. Ajak keluarga dan sahabat Anda."
            />

            {empty && (
                <p className="mx-auto max-w-7xl px-4 py-16 text-lg text-muted-foreground sm:px-6 lg:px-10">
                    Belum ada kegiatan yang diumumkan.
                </p>
            )}

            <Group
                id="berlangsung"
                eyebrow="Sekarang"
                title="Sedang berlangsung"
                events={ongoing}
            />
            <Group
                id="akan-datang"
                eyebrow="Catat tanggalnya"
                title="Akan datang"
                events={upcoming}
            />
            <Group
                id="sudah-lewat"
                eyebrow="Arsip"
                title="Sudah lewat"
                events={past}
            />

            <SiteFooter />
        </>
    );
}

EventsPublicIndex.layout = { nav };
