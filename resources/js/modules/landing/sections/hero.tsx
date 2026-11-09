import { ChevronLeft, ChevronRight, Pause, Play } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Glass } from '@/components/glass';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { sectionIds, SmartLink, Verse } from '../components';
import type { HeroContent } from '../types';

/** Time each slide stays on screen. */
const SLIDE_MS = 6000;

function prefersReducedMotion(): boolean {
    return (
        typeof window !== 'undefined' &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches
    );
}

/**
 * Theme slider in a fixed-size frame, so the page never jumps between
 * slides. It advances on its own, pauses while hovered or focused, and has
 * a pause button (WCAG 2.2.2). Visitors who ask for reduced motion start
 * paused.
 */
export function HeroSection({ content }: { content: HeroContent }) {
    const slides = content.slides;
    const [current, setCurrent] = useState(0);
    const [playing, setPlaying] = useState(true);
    const [hovered, setHovered] = useState(false);

    useEffect(() => {
        if (prefersReducedMotion()) {
            setPlaying(false);
        }
    }, []);

    const go = useCallback(
        (index: number) => setCurrent((index + slides.length) % slides.length),
        [slides.length],
    );

    const running = playing && !hovered && slides.length > 1;

    useEffect(() => {
        if (!running) {
            return;
        }

        const timer = window.setTimeout(() => go(current + 1), SLIDE_MS);

        return () => window.clearTimeout(timer);
    }, [running, current, go]);

    if (slides.length === 0) {
        return null;
    }

    return (
        <section
            id={sectionIds.hero}
            className="relative scroll-mt-24 overflow-hidden px-4 pt-8 pb-12 sm:px-6 sm:pt-14 sm:pb-20"
            aria-roledescription="carousel"
            aria-label="Tema utama"
        >
            <AppLogoIcon className="pointer-events-none absolute -top-10 -right-16 size-80 fill-flame/10 sm:size-[28rem]" />

            <Glass
                className="relative mx-auto flex h-[38rem] max-w-6xl flex-col overflow-hidden px-6 py-8 sm:h-[32rem] sm:px-12 sm:py-12"
                onMouseEnter={() => setHovered(true)}
                onMouseLeave={() => setHovered(false)}
                onFocus={() => setHovered(true)}
                onBlur={() => setHovered(false)}
            >
                {/* Every slide sits in the same box and cross-fades. */}
                <div
                    className="relative flex-1"
                    aria-live={running ? 'off' : 'polite'}
                >
                    {slides.map((slide, index) => (
                        <div
                            key={slide.title}
                            className={cn(
                                'absolute inset-0 flex flex-col justify-center transition-opacity duration-700 ease-out',
                                index === current
                                    ? 'opacity-100'
                                    : 'pointer-events-none opacity-0',
                            )}
                            aria-hidden={index !== current}
                            aria-roledescription="slide"
                            aria-label={`${index + 1} dari ${slides.length}`}
                        >
                            {slide.eyebrow && (
                                <p className="mb-3 text-sm font-semibold tracking-widest text-gold uppercase">
                                    {slide.eyebrow}
                                </p>
                            )}
                            <h1 className="line-clamp-3 font-serif text-3xl leading-tight font-semibold tracking-tight text-balance sm:text-5xl lg:text-6xl">
                                {slide.title}
                            </h1>
                            {slide.text && (
                                <p className="mt-5 line-clamp-4 max-w-2xl text-lg text-pretty text-muted-foreground">
                                    {slide.text}
                                </p>
                            )}
                            {slide.verse && (
                                <Verse
                                    text={slide.verse}
                                    reference={slide.reference}
                                    className="mt-6 max-w-2xl [&_blockquote]:line-clamp-5 [&_blockquote]:text-base sm:[&_blockquote]:text-xl"
                                />
                            )}
                        </div>
                    ))}
                </div>

                <div className="mt-6 flex flex-wrap items-center gap-3">
                    {content.primary_cta && (
                        <Button asChild size="lg" className="rounded-full">
                            <SmartLink href={content.primary_cta.href}>
                                {content.primary_cta.label}
                            </SmartLink>
                        </Button>
                    )}
                    {content.secondary_cta && (
                        <Button
                            asChild
                            size="lg"
                            variant="glass"
                            className="rounded-full"
                        >
                            <SmartLink href={content.secondary_cta.href}>
                                {content.secondary_cta.label}
                            </SmartLink>
                        </Button>
                    )}

                    {slides.length > 1 && (
                        <div className="flex items-center gap-2 sm:ml-auto">
                            <Button
                                variant="glass"
                                size="icon"
                                className="rounded-full"
                                onClick={() => setPlaying((value) => !value)}
                                aria-label={
                                    playing
                                        ? 'Hentikan slide otomatis'
                                        : 'Putar slide otomatis'
                                }
                            >
                                {playing ? <Pause /> : <Play />}
                            </Button>
                            <Button
                                variant="glass"
                                size="icon"
                                className="rounded-full"
                                onClick={() => go(current - 1)}
                                aria-label="Slide sebelumnya"
                            >
                                <ChevronLeft />
                            </Button>
                            <div className="flex gap-1.5">
                                {slides.map((item, index) => (
                                    <button
                                        key={item.title}
                                        type="button"
                                        onClick={() => go(index)}
                                        aria-label={`Ke slide ${index + 1}`}
                                        aria-current={index === current}
                                        className={cn(
                                            'h-2 rounded-full transition-all duration-300 ease-out',
                                            index === current
                                                ? 'w-6 bg-primary'
                                                : 'w-2 bg-foreground/25 hover:bg-foreground/40',
                                        )}
                                    />
                                ))}
                            </div>
                            <Button
                                variant="glass"
                                size="icon"
                                className="rounded-full"
                                onClick={() => go(current + 1)}
                                aria-label="Slide berikutnya"
                            >
                                <ChevronRight />
                            </Button>
                        </div>
                    )}
                </div>
            </Glass>
        </section>
    );
}
