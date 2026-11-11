import { Link } from '@inertiajs/react';
import { BookOpen, HandHeart, Pause, Play, Radio } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { publicButton } from '@/layouts/public-layout';
import { cn } from '@/lib/utils';
import { index as devotionals } from '@/routes/devotionals/public';
import { prayer } from '@/routes/landing';
import { sectionIds, SmartLink } from '../components';
import type { HeroContent, HeroLive, HeroSlide, HeroVideo } from '../types';

/** Time each theme slide stays on screen. */
const SLIDE_MS = 7000;

function prefersReducedMotion(): boolean {
    return (
        typeof window !== 'undefined' &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches
    );
}

/**
 * First homepage segment: a big welcome over the church's grainy gradient,
 * then a wide frame that shows, in order of priority, the live stream (while
 * Admin marks it live), Admin's uploaded videos (muted, one after another),
 * or just the theme slides. One button pauses all motion (WCAG 2.2.2);
 * visitors who ask for reduced motion start paused.
 */
export function HeroSection({ content }: { content: HeroContent }) {
    const slides = content.slides ?? [];
    const videos = content.videos ?? [];
    const live = content.live?.is_live ? content.live : null;

    const [playing, setPlaying] = useState(true);
    const [slide, setSlide] = useState(0);

    useEffect(() => {
        if (prefersReducedMotion()) {
            setPlaying(false);
        }
    }, []);

    useEffect(() => {
        if (!playing || slides.length < 2) {
            return;
        }

        const timer = window.setTimeout(
            () => setSlide((current) => (current + 1) % slides.length),
            SLIDE_MS,
        );

        return () => window.clearTimeout(timer);
    }, [playing, slide, slides.length]);

    const theme = slides[slide];
    const hasMotion = !live && (videos.length > 0 || slides.length > 1);

    return (
        <section
            id={sectionIds.hero}
            aria-label="Selamat datang"
            className="grain relative isolate scroll-mt-24 overflow-hidden bg-ember px-4 pt-12 pb-16 sm:px-6 sm:pt-20 sm:pb-24 lg:px-10"
        >
            <div
                aria-hidden="true"
                className="absolute bottom-8 left-6 hidden h-28 w-52 border-r border-b border-white/50 bg-[linear-gradient(rgb(255_255_255/0.5)_1px,transparent_1px),linear-gradient(90deg,rgb(255_255_255/0.5)_1px,transparent_1px)] bg-[size:22px_20px] sm:block lg:left-10"
            />

            <div className="relative mx-auto max-w-7xl text-center">
                <p className="font-condensed text-xs tracking-[0.32em] text-[#ffd2b8] sm:text-sm">
                    GEREJA GERAKAN PENTAKOSTA · CIPUTAT
                </p>
                <h1 className="mt-4 font-display text-6xl leading-[0.9] font-black tracking-[-0.045em] text-balance [text-shadow:0_0_48px_rgb(255_255_255/0.35)] sm:text-8xl lg:text-9xl">
                    {content.headline || 'Selamat Datang'}
                </h1>
                <p className="mt-4 font-condensed text-xl tracking-wide sm:text-3xl">
                    {content.subheadline || 'di GGP ELIM Rumah Kasih Karunia'}
                </p>

                <div
                    className={cn(
                        'relative mx-auto mt-10 aspect-[4/5] max-w-6xl overflow-hidden border border-white/15 bg-[#121218] text-left sm:mt-14 sm:aspect-video lg:aspect-[16/7]',
                        !live &&
                            videos.length === 0 &&
                            'bg-[radial-gradient(70%_80%_at_85%_15%,rgb(91_47_214/0.5),transparent_70%),radial-gradient(60%_70%_at_10%_95%,rgb(255_58_20/0.45),transparent_70%)]',
                    )}
                >
                    {live ? (
                        <LiveFrame live={live} />
                    ) : videos.length > 0 ? (
                        <VideoPlaylist videos={videos} playing={playing} />
                    ) : null}

                    {!live && (
                        <div className="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/85 via-black/20 to-transparent" />
                    )}

                    {!live && (
                        <div className="absolute inset-x-0 bottom-0 flex flex-col gap-5 p-5 sm:flex-row sm:items-end sm:justify-between sm:p-8">
                            {theme && <ThemeCaption slide={theme} />}
                            <div className="flex flex-wrap items-center gap-3">
                                {content.primary_cta && (
                                    <SmartLink
                                        href={content.primary_cta.href}
                                        className={publicButton.solid}
                                    >
                                        {content.primary_cta.label}
                                    </SmartLink>
                                )}
                                {content.secondary_cta && (
                                    <SmartLink
                                        href={content.secondary_cta.href}
                                        className={publicButton.outline}
                                    >
                                        {content.secondary_cta.label}
                                    </SmartLink>
                                )}
                                {hasMotion && (
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setPlaying((value) => !value)
                                        }
                                        aria-label={
                                            playing
                                                ? 'Hentikan video dan slide'
                                                : 'Putar video dan slide'
                                        }
                                        className="flex size-11 items-center justify-center border border-white/50 text-white transition-colors hover:bg-white/10"
                                    >
                                        {playing ? (
                                            <Pause className="size-4" />
                                        ) : (
                                            <Play className="size-4" />
                                        )}
                                    </button>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>

            <nav
                aria-label="Pintasan"
                className="absolute right-6 bottom-24 hidden flex-col gap-3 xl:flex"
            >
                <Link
                    href={prayer()}
                    aria-label="Form doa"
                    className="flex size-12 items-center justify-center rounded-full border border-white/45 bg-black/30 text-white transition-colors hover:bg-white/10"
                >
                    <HandHeart className="size-5" />
                </Link>
                <Link
                    href={devotionals()}
                    aria-label="Renungan harian"
                    className="flex size-12 items-center justify-center rounded-full border border-white/45 bg-black/30 text-white transition-colors hover:bg-white/10"
                >
                    <BookOpen className="size-5" />
                </Link>
            </nav>
        </section>
    );
}

function ThemeCaption({ slide }: { slide: HeroSlide }) {
    return (
        <div
            key={slide.title}
            className="max-w-2xl animate-in duration-700 fade-in"
            aria-live="polite"
        >
            {slide.eyebrow && (
                <p className="font-condensed text-xs tracking-[0.24em] text-[#ffb38a] uppercase sm:text-sm">
                    {slide.eyebrow}
                </p>
            )}
            <p className="mt-2 font-display text-2xl leading-tight font-extrabold tracking-tight text-balance text-white sm:text-4xl">
                {slide.title}
            </p>
            {slide.reference && (
                <p className="mt-2 text-sm text-white/75">{slide.reference}</p>
            )}
        </div>
    );
}

/**
 * Admin's videos, muted, one after another (the only one loops). Paused
 * together with the slides by the hero's pause button.
 */
function VideoPlaylist({
    videos,
    playing,
}: {
    videos: HeroVideo[];
    playing: boolean;
}) {
    const [index, setIndex] = useState(0);
    const ref = useRef<HTMLVideoElement>(null);
    const video = videos[index % videos.length];

    useEffect(() => {
        const element = ref.current;

        if (!element) {
            return;
        }

        if (playing) {
            // Browsers may refuse autoplay (data saver); the poster stays.
            element.play().catch(() => undefined);
        } else {
            element.pause();
        }
    }, [playing, index]);

    const next = useCallback(
        () => setIndex((current) => (current + 1) % videos.length),
        [videos.length],
    );

    return (
        <video
            key={video.id}
            ref={ref}
            className="absolute inset-0 size-full object-cover"
            src={video.url}
            poster={video.poster ?? undefined}
            muted
            playsInline
            preload="metadata"
            autoPlay={playing}
            loop={videos.length === 1}
            onEnded={next}
            aria-label={video.title ?? 'Video suasana ibadah GGP Elim'}
        />
    );
}

/** The live stream: an embedded YouTube player, else a button to it. */
function LiveFrame({ live }: { live: HeroLive }) {
    return (
        <>
            {live.embed_url ? (
                <iframe
                    src={live.embed_url}
                    title={
                        live.title
                            ? `Siaran langsung: ${live.title}`
                            : 'Siaran langsung GGP Elim'
                    }
                    className="absolute inset-0 size-full"
                    allow="autoplay; encrypted-media; picture-in-picture"
                    allowFullScreen
                    referrerPolicy="strict-origin-when-cross-origin"
                />
            ) : (
                <div className="absolute inset-0 flex flex-col items-center justify-center gap-6 p-6 text-center">
                    <p className="font-display text-3xl font-extrabold tracking-tight text-white sm:text-5xl">
                        {live.title ?? 'Ibadah sedang berlangsung'}
                    </p>
                    {live.url && (
                        <a
                            href={live.url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className={publicButton.solid}
                        >
                            <Play className="size-4" />
                            Tonton live
                        </a>
                    )}
                </div>
            )}
            <span
                className={cn(
                    'pointer-events-none absolute top-4 left-4 inline-flex items-center gap-2 bg-[#e5241b] px-3 py-1.5 font-condensed text-xs tracking-[0.2em] text-white',
                )}
            >
                <Radio className="size-3.5 motion-safe:animate-pulse" />
                LIVE{live.title ? ` · ${live.title.toUpperCase()}` : ''}
            </span>
        </>
    );
}
