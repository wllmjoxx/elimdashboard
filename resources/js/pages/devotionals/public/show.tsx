import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, ArrowRight } from 'lucide-react';
import { DevotionalArticle } from '@/modules/devotionals/devotional-article';
import type { PublicDevotional } from '@/modules/devotionals/types';
import { HeroBackLink, PageHero } from '@/modules/landing/components';
import { nav } from '@/modules/landing/nav';
import { Reveal } from '@/modules/landing/reveal';
import { SiteFooter } from '@/modules/landing/site-footer';
import { index } from '@/routes/devotionals/public';

type Props = {
    devotional: PublicDevotional;
    newer: PublicDevotional | null;
    older: PublicDevotional | null;
};

/** One devotional with links to the day before and after. */
export default function DevotionalsPublicShow({
    devotional,
    newer,
    older,
}: Props) {
    return (
        <>
            <Head title={devotional.title} />

            <PageHero
                before={
                    <HeroBackLink href={index.url()}>
                        Semua renungan
                    </HeroBackLink>
                }
                eyebrow={
                    <time dateTime={devotional.date}>
                        Renungan Harian · {devotional.dateLabel}
                    </time>
                }
                title={devotional.title}
            />

            <section className="px-4 py-16 sm:px-6 sm:py-20 lg:px-10">
                <Reveal className="mx-auto max-w-3xl space-y-14">
                    <DevotionalArticle
                        devotional={devotional}
                        showHeader={false}
                    />

                    {(newer || older) && (
                        <nav
                            aria-label="Renungan lain"
                            className="grid gap-px border border-foreground/10 bg-foreground/10 sm:grid-cols-2"
                        >
                            {older ? (
                                <Link
                                    href={older.url}
                                    className="bg-background p-5 transition-colors hover:bg-muted"
                                >
                                    <span className="flex items-center gap-1 font-condensed text-xs tracking-[0.2em] text-muted-foreground uppercase">
                                        <ArrowLeft className="size-3.5" />
                                        Sebelumnya
                                    </span>
                                    <span className="mt-2 block font-display text-lg font-extrabold tracking-tight">
                                        {older.title}
                                    </span>
                                </Link>
                            ) : (
                                <span className="bg-background" />
                            )}
                            {newer ? (
                                <Link
                                    href={newer.url}
                                    className="bg-background p-5 text-right transition-colors hover:bg-muted"
                                >
                                    <span className="flex items-center justify-end gap-1 font-condensed text-xs tracking-[0.2em] text-muted-foreground uppercase">
                                        Berikutnya
                                        <ArrowRight className="size-3.5" />
                                    </span>
                                    <span className="mt-2 block font-display text-lg font-extrabold tracking-tight">
                                        {newer.title}
                                    </span>
                                </Link>
                            ) : (
                                <span className="bg-background" />
                            )}
                        </nav>
                    )}
                </Reveal>
            </section>

            <SiteFooter />
        </>
    );
}

DevotionalsPublicShow.layout = { nav };
