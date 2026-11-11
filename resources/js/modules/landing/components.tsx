import { Link } from '@inertiajs/react';
import type { HTMLAttributes, ReactNode } from 'react';
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
    privacy: 'privasi',
    pastor: 'gembala',
    segment_1: 'segmen-1',
    segment_2: 'segmen-2',
    segment_3: 'segmen-3',
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
                'scroll-mt-24 px-4 py-16 sm:px-6 sm:py-24 lg:px-10',
                className,
            )}
        >
            <div className="mx-auto max-w-7xl">{children}</div>
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
                'mb-12 max-w-3xl',
                align === 'center' && 'mx-auto text-center',
            )}
        >
            {eyebrow && (
                <p className="mb-3 font-condensed text-sm tracking-[0.3em] text-muted-foreground uppercase">
                    {eyebrow}
                </p>
            )}
            <h2 className="font-display text-4xl leading-[0.95] font-black tracking-tight text-balance sm:text-6xl">
                {title}
            </h2>
            {subtitle && (
                <p className="mt-4 text-lg text-muted-foreground">{subtitle}</p>
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
        <figure
            className={cn('space-y-3 border-l-2 border-flame pl-5', className)}
        >
            <blockquote className="text-lg leading-relaxed text-pretty sm:text-xl">
                “{text}”
            </blockquote>
            {reference && (
                <figcaption className="font-condensed text-sm tracking-[0.2em] text-flame-strong uppercase">
                    {reference}
                </figcaption>
            )}
        </figure>
    );
}

export function SampleBadge() {
    return (
        <span className="inline-flex items-center border border-current/40 px-2.5 py-0.5 font-condensed text-xs tracking-[0.2em] text-muted-foreground uppercase">
            Contoh
        </span>
    );
}

/**
 * Link for admin-entered addresses: Inertia navigation for pages of this
 * site ("/jadwal"), a new tab for other websites.
 */
export function SmartLink({
    href,
    className,
    children,
}: {
    href: string;
    className?: string;
    children: ReactNode;
}) {
    if (href.startsWith('/')) {
        return (
            <Link href={href} className={className}>
                {children}
            </Link>
        );
    }

    return (
        <a
            href={href}
            target="_blank"
            rel="noopener noreferrer"
            className={className}
        >
            {children}
        </a>
    );
}

/**
 * Square, hairline-bordered surface for content blocks on public pages
 * (schedule groups, contact details, testimony cards).
 */
export function Panel({
    as: Component = 'div',
    className,
    ...props
}: HTMLAttributes<HTMLElement> & { as?: 'div' | 'article' | 'section' }) {
    return (
        <Component
            className={cn('border border-foreground/10 bg-card', className)}
            {...props}
        />
    );
}

/**
 * Top band of a public page: the church's grainy dark gradient with a
 * condensed eyebrow, a heavy title and an optional description. Extra
 * content (a back link, meta line) goes in `before` or `children`.
 */
export function PageHero({
    eyebrow = 'GGP Elim Ciputat',
    title,
    description,
    before,
    children,
}: {
    eyebrow?: ReactNode;
    title: ReactNode;
    description?: ReactNode;
    before?: ReactNode;
    children?: ReactNode;
}) {
    return (
        <header className="grain relative isolate overflow-hidden bg-ember px-4 pt-32 pb-14 sm:px-6 sm:pt-44 sm:pb-20 lg:px-10">
            <div className="relative mx-auto max-w-7xl">
                {before}
                <p className="mb-4 font-condensed text-sm tracking-[0.32em] text-band-accent uppercase">
                    {eyebrow}
                </p>
                <h1 className="max-w-5xl font-display text-5xl leading-[0.92] font-black tracking-[-0.04em] text-balance [text-shadow:var(--band-glow)] sm:text-7xl lg:text-8xl">
                    {title}
                </h1>
                {description && (
                    <p className="mt-6 max-w-2xl text-lg text-pretty text-band-muted">
                        {description}
                    </p>
                )}
                {children}
            </div>
        </header>
    );
}

/** Back link shown above a PageHero title. */
export function HeroBackLink({
    href,
    children,
}: {
    href: string;
    children: ReactNode;
}) {
    return (
        <Link
            href={href}
            className="mb-8 inline-flex items-center gap-2 font-condensed text-sm tracking-[0.2em] text-band-muted uppercase hover:text-band-fg"
        >
            <span aria-hidden="true">←</span>
            {children}
        </Link>
    );
}

/** Square page links under a paginated list. */
export function Pagination({
    label,
    links,
}: {
    label: string;
    links: { url: string | null; label: string; active: boolean }[];
}) {
    return (
        <nav aria-label={label} className="mt-10 flex flex-wrap gap-2">
            {links.map((link) =>
                link.url ? (
                    <Link
                        key={link.label}
                        href={link.url}
                        aria-current={link.active ? 'page' : undefined}
                        className={cn(
                            'border border-foreground/20 px-4 py-2 text-sm font-semibold transition-colors hover:border-foreground',
                            link.active &&
                                'border-foreground bg-foreground text-background',
                        )}
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                ) : null,
            )}
        </nav>
    );
}
