import { Head, Link } from '@inertiajs/react';
import { ArrowRight, BookOpen } from 'lucide-react';
import { Glass } from '@/components/glass';
import { cn } from '@/lib/utils';
import { DevotionalArticle } from '@/modules/devotionals/devotional-article';
import type { Paginated, PublicDevotional } from '@/modules/devotionals/types';
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

            <header className="px-4 pt-14 sm:px-6 sm:pt-20">
                <Reveal className="mx-auto max-w-3xl">
                    <p className="mb-3 flex items-center gap-2 text-sm font-semibold tracking-widest text-gold uppercase">
                        <BookOpen className="size-4" />
                        Renungan Harian
                    </p>
                    <p className="max-w-2xl text-lg text-pretty text-muted-foreground">
                        Firman Tuhan untuk menemani langkah Anda hari ini.
                    </p>
                </Reveal>
            </header>

            {latest ? (
                <section className="px-4 py-10 sm:px-6">
                    <Reveal className="mx-auto max-w-3xl">
                        <DevotionalArticle devotional={latest} />
                    </Reveal>
                </section>
            ) : (
                archive.current_page === 1 && (
                    <p className="mx-auto max-w-3xl px-4 py-16 text-muted-foreground sm:px-6">
                        Belum ada renungan yang terbit. Silakan kembali besok.
                    </p>
                )
            )}

            {others.length > 0 && (
                <section aria-labelledby="arsip" className="px-4 pb-16 sm:px-6">
                    <div className="mx-auto max-w-6xl">
                        <h2
                            id="arsip"
                            className="mb-6 font-serif text-2xl font-semibold sm:text-3xl"
                        >
                            Arsip renungan
                        </h2>
                        <ul className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {others.map((item) => (
                                <li key={item.id}>
                                    <Reveal className="h-full">
                                        <Glass
                                            as="article"
                                            className="flex h-full flex-col p-6"
                                        >
                                            <p className="text-xs font-semibold tracking-widest text-gold uppercase">
                                                <time dateTime={item.date}>
                                                    {item.dateLabel}
                                                </time>
                                            </p>
                                            <h3 className="mt-2 font-serif text-xl font-semibold">
                                                <Link
                                                    href={item.url}
                                                    className="hover:underline"
                                                >
                                                    {item.title}
                                                </Link>
                                            </h3>
                                            <p className="mt-1 text-sm text-muted-foreground">
                                                {item.reference}
                                            </p>
                                            <p className="mt-3 line-clamp-3 text-sm text-muted-foreground">
                                                {item.excerpt}
                                            </p>
                                            <Link
                                                href={item.url}
                                                className="mt-auto inline-flex items-center gap-1.5 pt-4 text-sm font-semibold text-flame-strong"
                                                aria-label={`Baca ${item.title}`}
                                            >
                                                Baca
                                                <ArrowRight className="size-4" />
                                            </Link>
                                        </Glass>
                                    </Reveal>
                                </li>
                            ))}
                        </ul>

                        {archive.last_page > 1 && (
                            <nav
                                aria-label="Halaman arsip"
                                className="mt-8 flex flex-wrap gap-2"
                            >
                                {archive.links.map((link) =>
                                    link.url ? (
                                        <Link
                                            key={link.label}
                                            href={link.url}
                                            className={cn(
                                                'rounded-full border px-4 py-1.5 text-sm',
                                                link.active &&
                                                    'bg-primary text-primary-foreground',
                                            )}
                                            dangerouslySetInnerHTML={{
                                                __html: link.label,
                                            }}
                                        />
                                    ) : null,
                                )}
                            </nav>
                        )}
                    </div>
                </section>
            )}

            <SiteFooter />
        </>
    );
}

DevotionalsPublicIndex.layout = { nav };
