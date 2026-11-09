import { ArrowRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
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
            className="relative isolate my-8 flex min-h-[26rem] scroll-mt-24 items-center overflow-hidden bg-neutral-950 px-4 py-20 text-white sm:my-12 sm:min-h-[32rem] sm:px-6"
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
                    className="absolute inset-0 -z-10 bg-[radial-gradient(60%_80%_at_10%_30%,rgb(from_var(--flame)_r_g_b/0.35),transparent_70%),radial-gradient(60%_80%_at_90%_70%,rgb(from_var(--sky)_r_g_b/0.35),transparent_70%),radial-gradient(50%_60%_at_50%_100%,rgb(from_var(--iris)_r_g_b/0.35),transparent_70%)]"
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

            <Reveal className="mx-auto w-full max-w-6xl">
                <div
                    className={cn(
                        'flex max-w-2xl flex-col',
                        alignment[content.align],
                    )}
                >
                    {content.eyebrow && (
                        <p className="mb-3 text-sm font-semibold tracking-widest text-gold uppercase">
                            {content.eyebrow}
                        </p>
                    )}
                    <h2 className="font-serif text-3xl leading-tight font-semibold tracking-tight text-balance sm:text-5xl">
                        {content.title}
                    </h2>
                    {content.text && (
                        <p className="mt-4 text-lg text-pretty text-white/80">
                            {content.text}
                        </p>
                    )}
                    {content.button_label && content.button_href && (
                        <Button asChild size="lg" className="mt-8 rounded-full">
                            <SmartLink href={content.button_href}>
                                {content.button_label}
                                <ArrowRight />
                            </SmartLink>
                        </Button>
                    )}
                </div>
            </Reveal>
        </section>
    );
}
