import { ArrowRight } from 'lucide-react';
import type { ReactNode } from 'react';
import { publicButton } from '@/layouts/public-layout';
import { cn } from '@/lib/utils';
import { sectionIds, SmartLink } from '../components';
import { Reveal } from '../reveal';
import type { PastorContent } from '../types';

const socials: {
    key: 'instagram' | 'youtube' | 'facebook';
    label: string;
    icon: ReactNode;
}[] = [
    {
        key: 'instagram',
        label: 'Instagram',
        icon: (
            <svg
                viewBox="0 0 24 24"
                className="size-4"
                fill="none"
                stroke="currentColor"
                strokeWidth="1.8"
                aria-hidden="true"
            >
                <rect x="3" y="3" width="18" height="18" rx="5" />
                <circle cx="12" cy="12" r="4" />
                <circle cx="17.5" cy="6.5" r="0.8" fill="currentColor" />
            </svg>
        ),
    },
    {
        key: 'youtube',
        label: 'YouTube',
        icon: (
            <svg
                viewBox="0 0 24 24"
                className="size-4"
                fill="none"
                stroke="currentColor"
                strokeWidth="1.8"
                aria-hidden="true"
            >
                <rect x="2.5" y="5.5" width="19" height="13" rx="4" />
                <path d="M10 9.5v5l4.5-2.5z" fill="currentColor" />
            </svg>
        ),
    },
    {
        key: 'facebook',
        label: 'Facebook',
        icon: (
            <svg
                viewBox="0 0 24 24"
                className="size-4"
                fill="none"
                stroke="currentColor"
                strokeWidth="1.8"
                aria-hidden="true"
            >
                <path d="M14 8h3V4h-3a4 4 0 0 0-4 4v3H7v4h3v6h4v-6h3l1-4h-4V8z" />
            </svg>
        ),
    },
];

/**
 * The senior pastor: a portrait in a panel with one big rounded corner
 * beside a grainy band with the name, a short bio, a "read more" link and
 * social accounts. Everything is set by Admin.
 */
export function PastorSection({ content }: { content: PastorContent }) {
    const links = socials.filter((social) => content[social.key]);

    return (
        <section
            id={sectionIds.pastor}
            aria-labelledby="gembala-nama"
            className="grain relative isolate scroll-mt-24 overflow-hidden bg-ember lg:grid lg:min-h-[44rem] lg:grid-cols-[minmax(0,5fr)_minmax(0,8fr)]"
        >
            <div className="relative h-[28rem] sm:h-[34rem] lg:h-auto">
                <div
                    aria-hidden="true"
                    className="absolute bottom-16 left-0 h-1/2 w-2/5 bg-gradient-to-b from-[#ff5a2a] to-[#c2187a]"
                />
                <div className="absolute inset-y-0 left-0 w-[calc(100%-1.5rem)] overflow-hidden rounded-tr-[9rem] bg-[#e9e1d6] sm:rounded-tr-[12rem] dark:bg-black">
                    {content.photo_url ? (
                        <img
                            src={content.photo_url}
                            alt={`Foto ${content.name}`}
                            loading="lazy"
                            decoding="async"
                            className="absolute inset-x-0 bottom-0 mx-auto h-[92%] w-auto max-w-full object-contain object-bottom"
                        />
                    ) : (
                        <div className="absolute inset-0 flex items-center justify-center p-8 text-center font-condensed text-xs tracking-[0.24em] text-band-muted uppercase">
                            Foto gembala belum diunggah
                        </div>
                    )}
                </div>
            </div>

            <Reveal className="relative flex flex-col justify-center px-4 py-16 text-band-fg sm:px-10 lg:px-16 xl:px-24">
                <p className="font-condensed text-sm tracking-[0.32em] text-band-accent uppercase">
                    {content.label}
                </p>
                <h2
                    id="gembala-nama"
                    className="mt-4 font-display text-5xl leading-[0.95] font-black tracking-[-0.04em] text-balance [text-shadow:var(--band-glow)] sm:text-7xl"
                >
                    {content.name}
                </h2>
                <div className="mt-7 max-w-2xl space-y-4 text-lg leading-relaxed text-band-muted">
                    {content.paragraphs.map((paragraph, index) => (
                        <p key={index}>{paragraph}</p>
                    ))}
                </div>
                {content.more_label && content.more_href && (
                    <div className="mt-9">
                        <SmartLink
                            href={content.more_href}
                            className={publicButton.solid}
                        >
                            {content.more_label}
                            <ArrowRight className="size-4" />
                        </SmartLink>
                    </div>
                )}
                {links.length > 0 && (
                    <ul className="mt-9 flex flex-wrap gap-2.5">
                        {links.map((social) => (
                            <li key={social.key}>
                                <a
                                    href={content[social.key] ?? '#'}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className={cn(
                                        'inline-flex items-center gap-2 rounded-lg glass-float px-4 py-2.5 text-xs font-semibold tracking-[0.12em] uppercase transition-colors hover:bg-band-fg/10',
                                    )}
                                    aria-label={`${social.label} ${content.social_name ?? content.name}`}
                                >
                                    {social.icon}
                                    {content.social_name || social.label}
                                </a>
                            </li>
                        ))}
                    </ul>
                )}
            </Reveal>
        </section>
    );
}
