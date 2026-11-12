import { Head, Link } from '@inertiajs/react';
import { ArrowRight, Pin } from 'lucide-react';
import { PageHero, Pagination } from '@/modules/landing/components';
import { nav } from '@/modules/landing/nav';
import { Reveal } from '@/modules/landing/reveal';
import { SiteFooter } from '@/modules/landing/site-footer';

type Item = {
    id: number;
    title: string;
    excerpt: string;
    isPinned: boolean;
    date: string;
    dateLabel: string;
    url: string;
};

type Props = {
    announcements: {
        data: Item[];
        links: { url: string | null; label: string; active: boolean }[];
        last_page: number;
    };
};

/** Public "Pengumuman": current announcements, pinned first. */
export default function AnnouncementsPublicIndex({ announcements }: Props) {
    return (
        <>
            <Head title="Pengumuman" />

            <PageHero
                eyebrow="Pengumuman"
                title="Kabar untuk jemaat"
                description="Informasi terbaru dari GGP Elim Ciputat."
            />

            <section className="px-4 py-16 sm:px-6 sm:py-24 lg:px-10">
                <div className="mx-auto max-w-4xl">
                    {announcements.data.length === 0 ? (
                        <p className="text-lg text-muted-foreground">
                            Belum ada pengumuman.
                        </p>
                    ) : (
                        <ul className="divide-y divide-foreground/10 border-y border-foreground/10">
                            {announcements.data.map((item) => (
                                <li key={item.id}>
                                    <Reveal>
                                        <Link
                                            href={item.url}
                                            className="group flex flex-col gap-2 py-7 sm:flex-row sm:items-start sm:gap-8"
                                        >
                                            <p className="shrink-0 font-condensed text-xs tracking-[0.2em] text-flame-strong uppercase sm:w-44 sm:pt-1.5">
                                                <time dateTime={item.date}>
                                                    {item.dateLabel}
                                                </time>
                                            </p>
                                            <div className="min-w-0 flex-1">
                                                {item.isPinned && (
                                                    <p className="mb-2 inline-flex items-center gap-1.5 font-condensed text-xs tracking-[0.2em] text-muted-foreground uppercase">
                                                        <Pin className="size-3.5" />
                                                        Disematkan
                                                    </p>
                                                )}
                                                <h2 className="font-display text-2xl leading-tight font-extrabold tracking-tight group-hover:underline sm:text-3xl">
                                                    {item.title}
                                                </h2>
                                                <p className="mt-2 line-clamp-2 text-muted-foreground">
                                                    {item.excerpt}
                                                </p>
                                            </div>
                                            <ArrowRight className="hidden size-5 shrink-0 text-muted-foreground transition-transform group-hover:translate-x-1 sm:mt-2 sm:block" />
                                        </Link>
                                    </Reveal>
                                </li>
                            ))}
                        </ul>
                    )}

                    {announcements.last_page > 1 && (
                        <Pagination
                            label="Halaman pengumuman"
                            links={announcements.links}
                        />
                    )}
                </div>
            </section>

            <SiteFooter />
        </>
    );
}

AnnouncementsPublicIndex.layout = { nav };
