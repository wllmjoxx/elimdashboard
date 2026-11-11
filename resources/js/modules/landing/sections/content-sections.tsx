import {
    Clock,
    ExternalLink,
    HandHeart,
    ImageIcon,
    MapPin,
    MessageCircle,
    Quote,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Panel,
    SampleBadge,
    SectionHeading,
    SectionShell,
    sectionIds,
    SmartLink,
    Verse,
} from '../components';
import type {
    AboutContent,
    ContactContent,
    DevotionalContent,
    GalleryContent,
    MissionContent,
    PrayerContent,
    PrivacyContent,
    ScheduleContent,
    ScheduleGroup,
    TestimoniesContent,
} from '../types';

export function AboutSection({ content }: { content: AboutContent }) {
    return (
        <SectionShell id={sectionIds.about}>
            <div className="grid gap-10 lg:grid-cols-5">
                <div className="lg:col-span-3">
                    <SectionHeading eyebrow="Sejarah" title={content.title} />
                    {/* Long text sits on a solid surface for readability. */}
                    <div className="space-y-4 rounded-glass border bg-background/90 p-6 text-lg leading-relaxed sm:p-8">
                        {content.paragraphs.map((paragraph) => (
                            <p key={paragraph}>{paragraph}</p>
                        ))}
                    </div>
                </div>
                {content.milestones && content.milestones.length > 0 && (
                    <ol className="space-y-4 self-end lg:col-span-2">
                        {content.milestones.map((milestone) => (
                            <li key={milestone.year}>
                                <Panel className="flex items-baseline gap-4 p-5">
                                    <span className="font-display text-2xl font-extrabold tracking-tight whitespace-nowrap text-flame-strong">
                                        {milestone.year}
                                    </span>
                                    <span>{milestone.text}</span>
                                </Panel>
                            </li>
                        ))}
                    </ol>
                )}
            </div>
        </SectionShell>
    );
}

export function MissionSection({ content }: { content: MissionContent }) {
    return (
        <SectionShell id={sectionIds.mission} className="text-center">
            <p className="mb-4 font-condensed text-sm tracking-[0.3em] text-muted-foreground uppercase">
                {content.title}
            </p>
            <p className="mx-auto max-w-3xl font-display text-3xl leading-[1.05] font-extrabold tracking-tight text-balance sm:text-5xl">
                {content.text}
            </p>
            {content.verse && (
                <Verse
                    text={content.verse}
                    reference={content.reference}
                    className="mx-auto mt-8 max-w-xl text-muted-foreground"
                />
            )}
        </SectionShell>
    );
}

export function DevotionalSection({ content }: { content: DevotionalContent }) {
    return (
        <SectionShell id={sectionIds.devotional}>
            <SectionHeading eyebrow={content.label} title={content.title} />
            <Panel as="article" className="max-w-3xl space-y-6 p-6 sm:p-10">
                <Verse text={content.verse} reference={content.reference} />
                <p className="text-lg leading-relaxed text-muted-foreground">
                    {content.excerpt}
                </p>
                {content.is_sample && <SampleBadge />}
            </Panel>
        </SectionShell>
    );
}

export function ScheduleSection({
    content,
    groups,
}: {
    content: ScheduleContent;
    groups: ScheduleGroup[];
}) {
    return (
        <SectionShell id={sectionIds.schedule}>
            <SectionHeading
                eyebrow="Jadwal"
                title={content.title}
                subtitle={content.subtitle}
            />
            <div className="grid gap-5 md:grid-cols-3">
                {groups.map((group) => (
                    <Panel key={group.category} className="p-6">
                        <h3 className="mb-4 font-display text-xl font-extrabold tracking-tight">
                            {group.label}
                        </h3>
                        <ul className="divide-y divide-foreground/10">
                            {group.items.map((item) => (
                                <li
                                    key={item.id}
                                    className="flex items-baseline justify-between gap-4 py-3"
                                >
                                    <span className="font-medium">
                                        {item.name}
                                    </span>
                                    <span className="text-right text-sm whitespace-nowrap text-muted-foreground">
                                        {item.day}{' '}
                                        <span className="font-semibold text-foreground tabular-nums">
                                            {item.time}
                                        </span>
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </Panel>
                ))}
            </div>
        </SectionShell>
    );
}

export function PrayerSection({ content }: { content: PrayerContent }) {
    return (
        <SectionShell id={sectionIds.prayer}>
            <Panel className="grid gap-8 p-6 sm:p-10 md:grid-cols-2 md:items-center">
                <div>
                    <SectionHeading eyebrow="Form Doa" title={content.title} />
                    <Verse text={content.verse} reference={content.reference} />
                </div>
                <div className="space-y-4">
                    <p className="text-lg text-muted-foreground">
                        {content.text}
                    </p>
                    {/* The form itself is built by the Prayer module. */}
                    <Button
                        size="lg"
                        className="rounded-none tracking-[0.12em] uppercase"
                        disabled
                    >
                        <HandHeart />
                        Form doa segera hadir
                    </Button>
                </div>
            </Panel>
        </SectionShell>
    );
}

const tileTints = [
    'from-sky/25 to-iris/20',
    'from-flame/25 to-gold/20',
    'from-iris/25 to-sky/20',
    'from-gold/25 to-flame/20',
    'from-sky/20 to-flame/20',
];

type GalleryItem = GalleryContent['items'][number];

function GalleryTile({
    item,
    index,
    className,
    natural = false,
}: {
    item: GalleryItem;
    index: number;
    className?: string;
    natural?: boolean;
}) {
    return (
        <figure
            className={`relative flex flex-col justify-end overflow-hidden rounded-glass border bg-background ${natural && item.image ? '' : 'aspect-[4/3] h-full'} ${className ?? ''}`}
        >
            {item.image ? (
                <img
                    src={item.image}
                    alt={item.caption || 'Foto kegiatan GGP Elim'}
                    loading="lazy"
                    decoding="async"
                    className={
                        natural
                            ? 'w-full'
                            : 'absolute inset-0 size-full object-cover'
                    }
                />
            ) : (
                <div
                    className={`absolute inset-0 grid place-items-center bg-gradient-to-br ${tileTints[index % tileTints.length]}`}
                    aria-hidden="true"
                >
                    <ImageIcon className="size-10 text-foreground/30" />
                </div>
            )}
            {item.caption && (
                <figcaption
                    className={`m-3 self-start bg-background/85 px-3 py-1 text-sm font-medium ${natural && item.image ? 'absolute bottom-0 left-0' : 'relative'}`}
                >
                    {item.caption}
                </figcaption>
            )}
        </figure>
    );
}

/**
 * Homepage gallery (PRD 4.3, 4.8) in the layout Admin picked: grid,
 * carousel or masonry.
 */
export function GallerySection({ content }: { content: GalleryContent }) {
    const layout = content.layout ?? 'grid';

    return (
        <SectionShell id={sectionIds.gallery}>
            <div className="flex flex-wrap items-end justify-between gap-4">
                <SectionHeading
                    eyebrow="Kegiatan"
                    title={content.title}
                    subtitle={content.album ?? undefined}
                />
                <div className="mb-10 flex flex-wrap items-center gap-2">
                    {content.is_sample && <SampleBadge />}
                    {content.url && (
                        <Button
                            asChild
                            variant="glass"
                            className="rounded-none tracking-[0.12em] uppercase"
                        >
                            <SmartLink href={content.url}>
                                Lihat album
                            </SmartLink>
                        </Button>
                    )}
                    {content.index_url && !content.is_sample && (
                        <Button
                            asChild
                            variant="ghost"
                            className="rounded-none tracking-[0.12em] uppercase"
                        >
                            <SmartLink href={content.index_url}>
                                Semua album
                            </SmartLink>
                        </Button>
                    )}
                </div>
            </div>

            {layout === 'carousel' ? (
                <ul
                    className="-mx-4 flex snap-x snap-mandatory [scrollbar-width:thin] gap-4 overflow-x-auto px-4 pb-4"
                    aria-label="Galeri foto"
                >
                    {content.items.map((item, index) => (
                        <li
                            key={index}
                            className="w-[80%] shrink-0 snap-start sm:w-[45%] lg:w-[30%]"
                        >
                            <GalleryTile item={item} index={index} />
                        </li>
                    ))}
                </ul>
            ) : layout === 'masonry' ? (
                <ul className="columns-2 gap-4 md:columns-3 [&>li]:mb-4">
                    {content.items.map((item, index) => (
                        <li key={index} className="break-inside-avoid">
                            <GalleryTile item={item} index={index} natural />
                        </li>
                    ))}
                </ul>
            ) : (
                <ul className="grid grid-cols-2 gap-4 md:grid-cols-4">
                    {content.items.map((item, index) => (
                        <li
                            key={index}
                            className={
                                index === 0
                                    ? 'col-span-2 md:row-span-2'
                                    : undefined
                            }
                        >
                            <GalleryTile item={item} index={index} />
                        </li>
                    ))}
                </ul>
            )}
        </SectionShell>
    );
}

export function TestimoniesSection({
    content,
}: {
    content: TestimoniesContent;
}) {
    const items = content.items ?? [];

    return (
        <SectionShell id={sectionIds.testimonies}>
            <div className="flex flex-wrap items-end justify-between gap-4">
                <SectionHeading
                    eyebrow="Kesaksian terpilih"
                    title={content.title}
                    subtitle={content.text}
                />
                {content.url && (
                    <div className="mb-10 flex flex-wrap gap-2">
                        <Button
                            asChild
                            variant="glass"
                            className="rounded-none tracking-[0.12em] uppercase"
                        >
                            <SmartLink href={content.url}>
                                Lihat semua
                            </SmartLink>
                        </Button>
                        <Button
                            asChild
                            className="rounded-none tracking-[0.12em] uppercase"
                        >
                            <SmartLink href={`${content.url}#bagikan`}>
                                <Quote />
                                Bagikan kesaksian
                            </SmartLink>
                        </Button>
                    </div>
                )}
            </div>
            {items.length === 0 ? (
                <p className="text-muted-foreground">
                    Belum ada kesaksian yang ditampilkan.
                </p>
            ) : (
                <ul
                    className="-mx-4 flex snap-x snap-mandatory [scrollbar-width:thin] gap-4 overflow-x-auto px-4 pb-4"
                    aria-label="Kesaksian terpilih"
                >
                    {items.map((item) => (
                        <li
                            key={item.id}
                            className="w-[85%] shrink-0 snap-start sm:w-[45%] lg:w-[32%]"
                        >
                            <Panel
                                as="article"
                                className="flex h-full flex-col p-6"
                            >
                                <Quote className="size-6 text-flame-strong" />
                                <h3 className="mt-3 font-display text-xl font-extrabold tracking-tight">
                                    {item.title}
                                </h3>
                                <p className="mt-3 flex-1 text-muted-foreground">
                                    {item.excerpt}
                                </p>
                                <p className="mt-4 text-sm font-medium">
                                    {item.name}{' '}
                                    <span className="text-muted-foreground">
                                        · {item.category}
                                    </span>
                                </p>
                            </Panel>
                        </li>
                    ))}
                </ul>
            )}
        </SectionShell>
    );
}

function whatsappLink(number: string): string {
    const digits = number.replace(/\D/g, '').replace(/^0/, '62');

    return `https://wa.me/${digits}`;
}

export function ContactSection({ content }: { content: ContactContent }) {
    const mapsUrl = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(content.maps_query)}`;

    return (
        <SectionShell id={sectionIds.contact}>
            <SectionHeading eyebrow="Kontak" title={content.title} />
            <Panel className="grid gap-6 p-6 sm:p-10 md:grid-cols-3">
                <div className="flex gap-3">
                    <MapPin className="mt-1 size-5 shrink-0 text-flame-strong" />
                    <div>
                        <h3 className="font-semibold">Alamat</h3>
                        <p className="text-muted-foreground">
                            {content.address}
                        </p>
                        <a
                            href={mapsUrl}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="mt-2 inline-flex items-center gap-1 text-sm font-medium text-primary underline-offset-4 hover:underline"
                        >
                            Buka di Google Maps
                            <ExternalLink className="size-3.5" />
                        </a>
                    </div>
                </div>
                <div className="flex gap-3">
                    <MessageCircle className="mt-1 size-5 shrink-0 text-flame-strong" />
                    <div>
                        <h3 className="font-semibold">WhatsApp</h3>
                        <a
                            href={whatsappLink(content.whatsapp)}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="text-primary underline-offset-4 hover:underline"
                        >
                            {content.whatsapp}
                        </a>
                    </div>
                </div>
                <div className="flex gap-3">
                    <Clock className="mt-1 size-5 shrink-0 text-flame-strong" />
                    <div>
                        <h3 className="font-semibold">Jam operasional</h3>
                        <p className="text-muted-foreground">{content.hours}</p>
                    </div>
                </div>
            </Panel>
        </SectionShell>
    );
}

/** Kebijakan Privasi: numbered points; each line of a body is a paragraph. */
export function PrivacySection({ content }: { content: PrivacyContent }) {
    return (
        <SectionShell id={sectionIds.privacy}>
            <div className="mx-auto max-w-3xl space-y-6 rounded-glass border bg-background/90 p-6 leading-relaxed sm:p-10">
                {content.effective && (
                    <p className="text-sm text-muted-foreground">
                        Berlaku sejak {content.effective}
                    </p>
                )}
                {content.intro && <p className="text-lg">{content.intro}</p>}
                <ol className="space-y-8">
                    {content.points.map((point, index) => (
                        <li key={point.heading} className="space-y-3">
                            <h2 className="font-display text-2xl font-extrabold tracking-tight">
                                {index + 1}. {point.heading}
                            </h2>
                            {point.body
                                .split('\n')
                                .filter((line) => line.trim() !== '')
                                .map((line) => (
                                    <p
                                        key={line}
                                        className="text-muted-foreground"
                                    >
                                        {line}
                                    </p>
                                ))}
                        </li>
                    ))}
                </ol>
            </div>
        </SectionShell>
    );
}
