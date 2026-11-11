import { ArrowRight } from 'lucide-react';
import { publicButton } from '@/layouts/public-layout';
import { cn } from '@/lib/utils';
import { SmartLink } from '../components';
import { Reveal } from '../reveal';
import type { SegmentContent } from '../types';

const alignment = {
    left: 'items-start text-left',
    center: 'items-center text-center mx-auto',
    right: 'items-end text-right ml-auto',
} as const;

/**
 * Full-width banner with a background image (set in the admin panel), a
 * dark overlay for readable white text, and an optional button. Without an
 * image it falls back to a soft brand gradient.
 */
export function SegmentSection({
    id,
    content,
}: {
    id: string;
    content: SegmentContent;
}) {
    return (
        <section
            id={id}
            className="relative isolate flex min-h-[28rem] scroll-mt-24 items-center overflow-hidden bg-[#050507] px-4 py-20 text-white sm:min-h-[36rem] sm:px-6 lg:px-10"
        >
            {content.image_url ? (
                <img
                    src={content.image_url}
                    alt=""
                    loading="lazy"
                    decoding="async"
                    className="absolute inset-0 -z-10 size-full object-cover"
                />
            ) : (
                <div
                    className="grain absolute inset-0 -z-10 bg-ember"
                    aria-hidden="true"
                />
            )}
            <div
                className={cn(
                    'absolute inset-0 -z-10',
                    content.align === 'center'
                        ? 'bg-black/55'
                        : content.align === 'right'
                          ? 'bg-gradient-to-l from-black/80 via-black/50 to-black/20'
                          : 'bg-gradient-to-r from-black/80 via-black/50 to-black/20',
                )}
                aria-hidden="true"
            />

            <Reveal className="mx-auto w-full max-w-7xl">
                <div
                    className={cn(
                        'flex max-w-3xl flex-col',
                        alignment[content.align],
                    )}
                >
                    {content.eyebrow && (
                        <p className="mb-3 font-condensed text-sm tracking-[0.3em] text-[#ffd2b8] uppercase">
                            {content.eyebrow}
                        </p>
                    )}
                    <h2 className="font-display text-4xl leading-[0.95] font-black tracking-tight text-balance sm:text-6xl lg:text-7xl">
                        {content.title}
                    </h2>
                    {content.text && (
                        <p className="mt-4 text-lg text-pretty text-white/80">
                            {content.text}
                        </p>
                    )}
                    {content.button_label && content.button_href && (
                        <SmartLink
                            href={content.button_href}
                            className={cn(publicButton.solid, 'mt-8')}
                        >
                            {content.button_label}
                            <ArrowRight className="size-4" />
                        </SmartLink>
                    )}
                </div>
            </Reveal>
        </section>
    );
}
