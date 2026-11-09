import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, ArrowRight } from 'lucide-react';
import { DevotionalArticle } from '@/modules/devotionals/devotional-article';
import type { PublicDevotional } from '@/modules/devotionals/types';
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
            <Head title={devotional.title}>
                <meta
                    name="description"
                    content={`${devotional.reference}: ${devotional.excerpt}`}
                />
                <meta
                    property="og:title"
                    content={`${devotional.title} · Renungan GGP Elim`}
                />
                <meta property="og:description" content={devotional.excerpt} />
                <meta property="og:type" content="article" />
            </Head>

            <section className="px-4 pt-14 pb-10 sm:px-6 sm:pt-20">
                <Reveal className="mx-auto max-w-3xl space-y-8">
                    <Link
                        href={index()}
                        className="inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" />
                        Semua renungan
                    </Link>

                    <DevotionalArticle devotional={devotional} />

                    {(newer || older) && (
                        <nav
                            aria-label="Renungan lain"
                            className="grid gap-4 sm:grid-cols-2"
                        >
                            {older ? (
                                <Link
                                    href={older.url}
                                    className="rounded-glass border p-4 hover:bg-foreground/5"
                                >
                                    <span className="flex items-center gap-1 text-xs text-muted-foreground">
                                        <ArrowLeft className="size-3.5" />
                                        Sebelumnya
                                    </span>
                                    <span className="mt-1 block font-serif font-semibold">
                                        {older.title}
                                    </span>
                                </Link>
                            ) : (
                                <span />
                            )}
                            {newer && (
                                <Link
                                    href={newer.url}
                                    className="rounded-glass border p-4 text-right hover:bg-foreground/5"
                                >
                                    <span className="flex items-center justify-end gap-1 text-xs text-muted-foreground">
                                        Berikutnya
                                        <ArrowRight className="size-3.5" />
                                    </span>
                                    <span className="mt-1 block font-serif font-semibold">
                                        {newer.title}
                                    </span>
                                </Link>
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
