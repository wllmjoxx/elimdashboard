import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { index as devotionalsPage } from '@/routes/devotionals/public';
import { schedule as schedulePage } from '@/routes/landing';
import { SampleBadge, SectionShell, Verse } from '../components';
import { Reveal } from '../reveal';
import type { DevotionalContent, ScheduleGroup } from '../types';

const outlineButton =
    'inline-flex items-center gap-2 border border-foreground/40 px-5 py-3 text-[13px] font-semibold tracking-[0.12em] uppercase transition-colors duration-200 hover:border-foreground hover:bg-foreground/5';

/**
 * Homepage teasers: a strip with the Sunday services, then today's
 * devotional as an editorial block. Each links to its own page.
 */
export function HomeHighlights({
    devotional,
    groups,
}: {
    devotional?: DevotionalContent;
    groups?: ScheduleGroup[];
}) {
    const sunday =
        groups?.find((group) => group.category === 'raya') ?? groups?.[0];

    if (!devotional && !sunday) {
        return null;
    }

    return (
        <>
            {sunday && (
                <section
                    aria-label={`Jadwal ${sunday.label}`}
                    className="border-b border-foreground/10 bg-muted/40 px-4 sm:px-6 lg:px-10"
                >
                    <div className="mx-auto grid max-w-7xl sm:grid-cols-2 lg:grid-cols-[repeat(auto-fit,minmax(0,1fr))]">
                        {sunday.items.map((item, index) => (
                            <div
                                key={item.id}
                                className={
                                    index === 0
                                        ? 'py-7 pr-6'
                                        : 'border-foreground/10 py-7 pr-6 max-sm:border-t sm:border-l sm:pl-6'
                                }
                            >
                                <p className="font-condensed text-xs tracking-[0.24em] text-flame-strong uppercase">
                                    {item.day} · {item.time}
                                </p>
                                <p className="mt-2 font-display text-xl font-extrabold tracking-tight">
                                    {item.name}
                                </p>
                            </div>
                        ))}
                        <Link
                            href={schedulePage()}
                            className="flex items-center justify-between gap-4 border-foreground/10 py-7 text-[13px] font-semibold tracking-[0.12em] uppercase max-sm:border-t sm:border-l sm:pl-6"
                        >
                            Lihat semua jadwal
                            <ArrowRight className="size-4" />
                        </Link>
                    </div>
                </section>
            )}

            {devotional && (
                <SectionShell id="sorotan">
                    <Reveal className="grid items-start gap-12 lg:grid-cols-2 lg:gap-16">
                        <div>
                            <p className="font-condensed text-sm tracking-[0.3em] text-muted-foreground uppercase">
                                {devotional.label}
                            </p>
                            <h2 className="mt-4 font-display text-4xl leading-[0.95] font-black tracking-tight text-balance sm:text-6xl">
                                {devotional.title}
                            </h2>
                            {devotional.is_sample && (
                                <div className="mt-4">
                                    <SampleBadge />
                                </div>
                            )}
                            <Link
                                href={devotional.url ?? devotionalsPage.url()}
                                className={`${outlineButton} mt-8`}
                            >
                                Baca renungan
                                <ArrowRight className="size-4" />
                            </Link>
                        </div>
                        <div>
                            {devotional.verse ? (
                                <Verse
                                    text={devotional.verse}
                                    reference={devotional.reference}
                                    className="[&_blockquote]:text-2xl"
                                />
                            ) : (
                                <p className="font-condensed text-sm tracking-[0.2em] text-flame-strong uppercase">
                                    {devotional.reference}
                                </p>
                            )}
                            <p className="mt-6 line-clamp-4 text-lg text-muted-foreground">
                                {devotional.excerpt}
                            </p>
                        </div>
                    </Reveal>
                </SectionShell>
            )}
        </>
    );
}
