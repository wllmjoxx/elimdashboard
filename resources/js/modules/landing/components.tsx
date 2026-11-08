import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

/** Anchor id of each section, used by the navbar links. */
export const sectionIds = {
    hero: 'beranda',
    about: 'tentang',
    mission: 'misi',
    devotional: 'renungan',
    schedule: 'jadwal',
    prayer: 'doa',
    gallery: 'galeri',
    testimonies: 'kesaksian',
    contact: 'kontak',
} as const;

export function SectionShell({
    id,
    children,
    className,
}: {
    id: string;
    children: ReactNode;
    className?: string;
}) {
    return (
        <section
            id={id}
            className={cn(
                'scroll-mt-24 px-4 py-16 sm:px-6 sm:py-24',
                className,
            )}
        >
            <div className="mx-auto max-w-6xl">{children}</div>
        </section>
    );
}

export function SectionHeading({
    eyebrow,
    title,
    subtitle,
    align = 'left',
}: {
    eyebrow?: string;
    title: string;
    subtitle?: string;
    align?: 'left' | 'center';
}) {
    return (
        <header
            className={cn(
                'mb-10 max-w-2xl',
                align === 'center' && 'mx-auto text-center',
            )}
        >
            {eyebrow && (
                <p className="mb-2 text-sm font-semibold tracking-widest text-gold uppercase">
                    {eyebrow}
                </p>
            )}
            <h2 className="font-serif text-3xl font-semibold tracking-tight text-balance sm:text-4xl">
                {title}
            </h2>
            {subtitle && (
                <p className="mt-3 text-muted-foreground">{subtitle}</p>
            )}
        </header>
    );
}

export function Verse({
    text,
    reference,
    className,
}: {
    text: string;
    reference?: string;
    className?: string;
}) {
    return (
        <figure className={cn('space-y-2', className)}>
            <blockquote className="font-serif text-lg leading-relaxed text-pretty italic sm:text-xl">
                “{text}”
            </blockquote>
            {reference && (
                <figcaption className="text-sm font-semibold tracking-wide text-gold">
                    {reference}
                </figcaption>
            )}
        </figure>
    );
}

export function SampleBadge() {
    return (
        <span className="inline-flex items-center rounded-full border border-gold/40 px-2.5 py-0.5 text-xs font-medium text-gold">
            Contoh
        </span>
    );
}
