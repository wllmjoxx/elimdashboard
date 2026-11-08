import { ChevronLeft, ChevronRight } from 'lucide-react';
import { useState } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Glass } from '@/components/glass';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { sectionIds, Verse } from '../components';
import type { HeroContent } from '../types';

export function HeroSection({ content }: { content: HeroContent }) {
    const slides = content.slides;
    const [current, setCurrent] = useState(0);
    const slide = slides[current];

    if (!slide) {
        return null;
    }

    const go = (index: number) =>
        setCurrent((index + slides.length) % slides.length);

    return (
        <section
            id={sectionIds.hero}
            className="relative scroll-mt-24 overflow-hidden px-4 pt-12 pb-16 sm:px-6 sm:pt-20 sm:pb-24"
            aria-roledescription="carousel"
            aria-label="Tema utama"
        >
            <AppLogoIcon className="pointer-events-none absolute -top-10 -right-16 size-80 fill-flame/10 sm:size-[28rem]" />

            <Glass className="relative mx-auto max-w-4xl px-6 py-12 sm:px-12 sm:py-16">
                <div
                    key={current}
                    className="animate-in duration-300 ease-out fade-in"
                    aria-live="polite"
                    aria-roledescription="slide"
                    aria-label={`${current + 1} dari ${slides.length}`}
                >
                    {slide.eyebrow && (
                        <p className="mb-3 text-sm font-semibold tracking-widest text-gold uppercase">
                            {slide.eyebrow}
                        </p>
                    )}
                    <h1 className="font-serif text-4xl leading-tight font-semibold tracking-tight text-balance sm:text-6xl">
                        {slide.title}
                    </h1>
                    {slide.text && (
                        <p className="mt-5 max-w-2xl text-lg text-pretty text-muted-foreground">
                            {slide.text}
                        </p>
                    )}
                    {slide.verse && (
                        <Verse
                            text={slide.verse}
                            reference={slide.reference}
                            className="mt-6 max-w-2xl"
                        />
                    )}
                </div>

                <div className="mt-10 flex flex-wrap items-center gap-3">
                    {content.primary_cta && (
                        <Button asChild size="lg" className="rounded-full">
                            <a href={content.primary_cta.href}>
                                {content.primary_cta.label}
                            </a>
                        </Button>
                    )}
                    {content.secondary_cta && (
                        <Button
                            asChild
                            size="lg"
                            variant="glass"
                            className="rounded-full"
                        >
                            <a href={content.secondary_cta.href}>
                                {content.secondary_cta.label}
                            </a>
                        </Button>
                    )}

                    {slides.length > 1 && (
                        <div className="ml-auto flex items-center gap-2">
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
