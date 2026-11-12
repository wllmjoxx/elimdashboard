import { Head } from '@inertiajs/react';
import { HeroBackLink, PageHero } from '@/modules/landing/components';
import { nav } from '@/modules/landing/nav';
import { Reveal } from '@/modules/landing/reveal';
import { SiteFooter } from '@/modules/landing/site-footer';
import { index } from '@/routes/announcements/public';

type Props = {
    announcement: {
        title: string;
        body: string;
        date: string;
        dateLabel: string;
    };
};

/** One announcement. */
export default function AnnouncementsPublicShow({ announcement }: Props) {
    return (
        <>
            <Head title={announcement.title} />

            <PageHero
                before={
                    <HeroBackLink href={index.url()}>
                        Semua pengumuman
                    </HeroBackLink>
                }
                eyebrow={
                    <time dateTime={announcement.date}>
                        Pengumuman · {announcement.dateLabel}
                    </time>
                }
                title={announcement.title}
            />

            <section className="px-4 py-16 sm:px-6 sm:py-20 lg:px-10">
                <Reveal className="mx-auto max-w-3xl space-y-5 text-lg leading-relaxed">
                    {announcement.body
                        .split(/\r?\n\s*\r?\n/)
                        .map((paragraph, i) => (
                            <p key={i} className="whitespace-pre-line">
                                {paragraph}
                            </p>
                        ))}
                </Reveal>
            </section>

            <SiteFooter />
        </>
    );
}

AnnouncementsPublicShow.layout = { nav };
