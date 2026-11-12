import { Head } from '@inertiajs/react';
import { CalendarDays, Clock, ExternalLink, MapPin } from 'lucide-react';
import { publicButton } from '@/layouts/public-layout';
import type { PublicEvent } from '@/modules/events/types';
import { HeroBackLink, PageHero } from '@/modules/landing/components';
import { nav } from '@/modules/landing/nav';
import { Reveal } from '@/modules/landing/reveal';
import { SiteFooter } from '@/modules/landing/site-footer';
import { index } from '@/routes/events/public';

type Props = { event: PublicEvent };

/** One event: when, where, the description and the sign-up link. */
export default function EventsPublicShow({ event }: Props) {
    return (
        <>
            <Head title={event.title} />

            <PageHero
                before={
                    <HeroBackLink href={index.url()}>
                        Semua kegiatan
                    </HeroBackLink>
                }
                eyebrow={`Kegiatan · ${event.stateLabel}`}
                title={event.title}
                description={event.summary ?? undefined}
            >
                {event.registrationUrl && event.state !== 'past' && (
                    <a
                        href={event.registrationUrl}
                        target="_blank"
                        rel="noopener noreferrer"
                        className={`${publicButton.solid} mt-8`}
                    >
                        Daftar sekarang
                        <ExternalLink className="size-4" />
                    </a>
                )}
            </PageHero>

            <section className="px-4 py-16 sm:px-6 sm:py-20 lg:px-10">
                <Reveal className="mx-auto grid max-w-7xl gap-12 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
                    <div className="space-y-8">
                        {event.imageUrl && (
                            <img
                                src={event.imageUrl}
                                alt={`Poster ${event.title}`}
                                className="w-full rounded-2xl border border-foreground/10 object-cover"
                            />
                        )}
                        {event.body && (
                            <div className="space-y-5 text-lg leading-relaxed">
                                {event.body
                                    .split(/\n{2,}|\r\n\r\n/)
                                    .map((paragraph, i) => (
                                        <p
                                            key={i}
                                            className="whitespace-pre-line"
                                        >
                                            {paragraph}
                                        </p>
                                    ))}
                            </div>
                        )}
                    </div>
                    <dl className="h-fit space-y-5 rounded-2xl border border-foreground/10 bg-card p-6">
                        <div>
                            <dt className="flex items-center gap-2 font-condensed text-xs tracking-[0.2em] text-muted-foreground uppercase">
                                <CalendarDays className="size-4" />
                                Tanggal
                            </dt>
                            <dd className="mt-1 font-display text-lg font-bold">
                                {event.dateLabel}
                            </dd>
                        </div>
                        <div>
                            <dt className="flex items-center gap-2 font-condensed text-xs tracking-[0.2em] text-muted-foreground uppercase">
                                <Clock className="size-4" />
                                Waktu
                            </dt>
                            <dd className="mt-1 font-display text-lg font-bold">
                                {event.timeLabel}
                            </dd>
                        </div>
                        {event.location && (
                            <div>
                                <dt className="flex items-center gap-2 font-condensed text-xs tracking-[0.2em] text-muted-foreground uppercase">
                                    <MapPin className="size-4" />
                                    Lokasi
                                </dt>
                                <dd className="mt-1 font-display text-lg font-bold">
                                    {event.location}
                                </dd>
                            </div>
                        )}
                    </dl>
                </Reveal>
            </section>

            <SiteFooter />
        </>
    );
}

EventsPublicShow.layout = { nav };
