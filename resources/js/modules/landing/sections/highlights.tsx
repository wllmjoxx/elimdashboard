import { Link } from '@inertiajs/react';
import { ArrowRight, BookOpen, CalendarDays } from 'lucide-react';
import { Glass } from '@/components/glass';
import { index as devotionalsPage } from '@/routes/devotionals/public';
import { schedule as schedulePage } from '@/routes/landing';
import { SampleBadge, SectionShell, Verse } from '../components';
import { Reveal } from '../reveal';
import type { DevotionalContent, ScheduleGroup } from '../types';

function MoreLink({ href, children }: { href: string; children: string }) {
    return (
        <Link
            href={href}
            className="mt-auto inline-flex items-center gap-1.5 pt-6 text-sm font-semibold text-flame-strong underline-offset-4 hover:underline"
        >
            {children}
            <ArrowRight className="size-4" />
        </Link>
    );
}

/**
 * Homepage teasers side by side: today's devotional and the Sunday
 * services, each linking to its own page.
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
        <SectionShell id="sorotan" className="py-12 sm:py-16">
            <Reveal className="grid gap-5 lg:grid-cols-5">
                {devotional && (
                    <Glass
                        as="article"
                        className="flex flex-col p-6 sm:p-8 lg:col-span-3"
                    >
                        <p className="mb-3 flex items-center gap-2 text-sm font-semibold tracking-widest text-gold uppercase">
                            <BookOpen className="size-4" />
                            {devotional.label}
                        </p>
                        <h2 className="font-serif text-2xl font-semibold tracking-tight sm:text-3xl">
                            {devotional.title}
                        </h2>
                        {devotional.verse ? (
                            <Verse
                                text={devotional.verse}
                                reference={devotional.reference}
                                className="mt-4"
                            />
                        ) : (
                            <p className="mt-4 text-sm font-semibold tracking-wide text-gold">
                                {devotional.reference}
                            </p>
                        )}
                        <p className="mt-4 line-clamp-3 text-muted-foreground">
                            {devotional.excerpt}
                        </p>
                        {devotional.is_sample && (
                            <div className="mt-4">
                                <SampleBadge />
                            </div>
                        )}
                        <MoreLink
                            href={devotional.url ?? devotionalsPage.url()}
                        >
                            Baca renungan
                        </MoreLink>
                    </Glass>
                )}
                {sunday && (
                    <Glass className="flex flex-col p-6 sm:p-8 lg:col-span-2">
                        <p className="mb-3 flex items-center gap-2 text-sm font-semibold tracking-widest text-gold uppercase">
                            <CalendarDays className="size-4" />
                            Jadwal
                        </p>
                        <h2 className="font-serif text-2xl font-semibold tracking-tight sm:text-3xl">
                            {sunday.label}
                        </h2>
                        <ul className="mt-4 divide-y divide-foreground/10">
                            {sunday.items.map((item) => (
                                <li
                                    key={item.id}
                                    className="flex items-baseline justify-between gap-4 py-3"
                                >
                                    <span className="font-medium">
                                        {item.name}
                                    </span>
                                    <span className="text-sm whitespace-nowrap text-muted-foreground">
                                        {item.day}{' '}
                                        <span className="font-semibold text-foreground tabular-nums">
                                            {item.time}
                                        </span>
                                    </span>
                                </li>
                            ))}
                        </ul>
                        <MoreLink href={schedulePage.url()}>
                            Lihat semua jadwal
                        </MoreLink>
                    </Glass>
                )}
            </Reveal>
        </SectionShell>
    );
}
