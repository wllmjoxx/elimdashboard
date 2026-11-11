import { Head, Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { DevotionalArticle } from '@/modules/devotionals/devotional-article';
import type { Paginated, PublicDevotional } from '@/modules/devotionals/types';
import { PageHero, Pagination } from '@/modules/landing/components';
import { nav } from '@/modules/landing/nav';
import { Reveal } from '@/modules/landing/reveal';
import { SiteFooter } from '@/modules/landing/site-footer';

type Props = {
    latest: PublicDevotional | null;
    archive: Paginated<PublicDevotional>;
};

/** "Renungan Harian": today's devotional and the archive (PRD 4.3, 4.8). */
export default function DevotionalsPublicIndex({ latest, archive }: Props) {
    const others = latest
        ? archive.data.filter((item) => item.id !== latest.id)
        : archive.data;

    return (
        <>
            <Head title="Renungan Harian" />

            <PageHero
                eyebrow="Renungan Harian"
                title="Firman untuk hari ini"
                description="Firman Tuhan untuk menemani langkah Anda hari ini."
            />

            {latest ? (
                <section className="px-4 py-16 sm:px-6 sm:py-24 lg:px-10">
                    <Reveal className="mx-auto max-w-3xl">
                        <DevotionalArticle
                            devotional={latest}
                            headingLevel="h2"
                        />
                    </Reveal>
                </section>
            ) : (
                archive.current_page === 1 && (
                    <p className="mx-auto max-w-3xl px-4 py-16 text-lg text-muted-foreground sm:px-6">
                        Belum ada renungan yang terbit. Silakan kembali besok.
                    </p>
                )
            )}

            {others.length > 0 && (
                <section
                    aria-labelledby="arsip"
                    className="border-t border-foreground/10 px-4 py-16 sm:px-6 sm:py-24 lg:px-10"
                >
                    <div className="mx-auto max-w-7xl">
                        <p className="font-condensed text-sm tracking-[0.3em] text-muted-foreground uppercase">
                            Arsip
                        </p>
                        <h2
                            id="arsip"
                            className="mt-3 mb-10 font-display text-4xl leading-[0.95] font-black tracking-tight sm:text-5xl"
                        >
                            Renungan sebelumnya
                        </h2>
                        <ul className="grid gap-px border border-foreground/10 bg-foreground/10 sm:grid-cols-2 lg:grid-cols-3">
                            {others.map((item) => (
                                <li key={item.id} className="bg-background">
                                    <Reveal className="h-full">
                                        <article className="flex h-full flex-col p-6 sm:p-8">
                                            <p className="font-condensed text-xs tracking-[0.24em] text-flame-strong uppercase">
                                                <time dateTime={item.date}>
                                                    {item.dateLabel}
                                                </time>
                                            </p>
                                            <h3 className="mt-3 font-display text-2xl leading-tight font-extrabold tracking-tight">
                                                <Link
                                                    href={item.url}
                                                    className="hover:underline"
                                                >
                                                    {item.title}
                                                </Link>
                                            </h3>
                                            <p className="mt-2 text-sm text-muted-foreground">
                                                {item.reference}
                                            </p>
                                            <p className="mt-3 line-clamp-3 text-muted-foreground">
                                                {item.excerpt}
                                            </p>
                                            <Link
                                                href={item.url}
                                                className="mt-auto inline-flex items-center gap-2 pt-6 text-[13px] font-semibold tracking-[0.12em] uppercase"
                                                aria-label={`Baca ${item.title}`}
                                            >
                                                Baca
                                                <ArrowRight className="size-4" />
                                            </Link>
                                        </article>
                                    </Reveal>
                                </li>
                            ))}
                        </ul>

                        {archive.last_page > 1 && (
                            <Pagination
                                label="Halaman arsip"
                                links={archive.links}
                            />
                        )}
                    </div>
                </section>
            )}

            <SiteFooter />
        </>
    );
}

DevotionalsPublicIndex.layout = { nav };
