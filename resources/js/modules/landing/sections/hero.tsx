import { Link } from '@inertiajs/react';
import { BookOpen, HandHeart, Pause, Play, Radio } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import type { ReactNode } from 'react';
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
 * First homepage segment: a big welcome over the church's grainy band.
 * Admin picks how the uploaded videos show: in a wide frame under the
 * welcome ("frame") or full width behind it, softly blurred
 * ("background"). A live stream always plays in the frame, in front. One
 * button pauses all motion (WCAG 2.2.2); visitors who ask for reduced
 * motion start paused.
 */
export function HeroSection({ content }: { content: HeroContent }) {
    const slides = content.slides ?? [];
    const videos = content.videos ?? [];
    const live = content.live?.is_live ? content.live : null;
    const background = content.video_mode === 'background' && videos.length > 0;

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
    const hasMotion = videos.length > 0 || slides.length > 1;
    const pauseButton = hasMotion ? (
        <button
            type="button"
            onClick={() => setPlaying((value) => !value)}
            aria-label={
                playing ? 'Hentikan video dan slide' : 'Putar video dan slide'
            }
            className="flex size-12 shrink-0 items-center justify-center rounded-full glass-float text-band-fg transition-colors hover:bg-band-fg/10"
        >
            {playing ? (
                <Pause className="size-4" />
            ) : (
                <Play className="size-4" />
            )}
        </button>
    ) : null;

    const ctas = (
        <>
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
                    className={publicButton.glass}
                >
                    {content.secondary_cta.label}
                </SmartLink>
            )}
        </>
    );

    return (
        <section
            id={sectionIds.hero}
            aria-label="Selamat datang"
            className={cn(
                'grain relative isolate scroll-mt-24 overflow-hidden bg-ember px-4 pt-32 pb-16 sm:px-6 sm:pt-40 sm:pb-24 lg:px-10',
                background && 'flex min-h-[100svh] flex-col',
            )}
        >
            {background && (
                <BackgroundVideo videos={videos} playing={playing} />
            )}

            <div
                aria-hidden="true"
                className="absolute bottom-8 left-6 hidden h-28 w-52 border-r border-b border-band-line bg-[linear-gradient(var(--band-line)_1px,transparent_1px),linear-gradient(90deg,var(--band-line)_1px,transparent_1px)] bg-[size:22px_20px] sm:block lg:left-10"
            />

            <div className="relative mx-auto w-full max-w-7xl text-center">
                <p className="font-condensed text-xs tracking-[0.32em] text-band-accent sm:text-sm">
                    GEREJA GERAKAN PENTAKOSTA · CIPUTAT
                </p>
                <h1 className="mt-4 font-display text-6xl leading-[0.9] font-black tracking-[-0.045em] text-balance [text-shadow:var(--band-glow)] sm:text-8xl lg:text-9xl">
                    {content.headline || 'Selamat Datang'}
                </h1>
                <p className="mt-4 font-condensed text-xl tracking-wide sm:text-3xl">
                    {content.subheadline || 'di GGP ELIM Rumah Kasih Karunia'}
                </p>

                {background && !live && (
                    <div className="mt-10 flex flex-wrap justify-center gap-3">
                        {ctas}
                    </div>
                )}

                {(!background || live) && (
                    <MediaFrame
                        live={live}
                        videos={background ? [] : videos}
                        playing={playing}
                    >
                        {!live && (
                            <div className="absolute inset-x-0 bottom-0 flex flex-col gap-4 p-4 sm:flex-row sm:items-end sm:justify-between sm:p-6">
                                {theme && <ThemeCard slide={theme} />}
                                <div className="flex flex-wrap items-center gap-3">
                                    {ctas}
                                    {pauseButton}
                                </div>
                            </div>
                        )}
                    </MediaFrame>
                )}
            </div>

            {background && !live && (
                <div className="relative mx-auto mt-auto flex w-full max-w-7xl flex-col gap-4 pt-16 sm:flex-row sm:items-end sm:justify-between">
                    {theme ? <ThemeCard slide={theme} /> : <span />}
                    {pauseButton}
                </div>
            )}

            <nav
                aria-label="Pintasan"
                className="absolute top-1/2 right-6 hidden -translate-y-1/2 flex-col gap-3 xl:flex"
            >
                <Link
                    href={prayer()}
                    aria-label="Form doa"
                    className="flex size-12 items-center justify-center rounded-full glass-float text-band-fg transition-colors hover:bg-band-fg/10"
                >
                    <HandHeart className="size-5" />
                </Link>
                <Link
                    href={devotionals()}
                    aria-label="Renungan harian"
                    className="flex size-12 items-center justify-center rounded-full glass-float text-band-fg transition-colors hover:bg-band-fg/10"
                >
                    <BookOpen className="size-5" />
                </Link>
            </nav>
        </section>
    );
}

/**
 * Wide frame under the welcome. Always dark inside (it holds a video or a
 * live player), so it forces the dark tokens for what sits on top.
 */
function MediaFrame({
    live,
    videos,
    playing,
    children,
}: {
    live: HeroLive | null;
    videos: HeroVideo[];
    playing: boolean;
    children?: ReactNode;
}) {
    // Dark only when it shows a video or the live player; an empty frame
    // follows the light or dark theme like the rest of the band.
    const hasMedia = live !== null || videos.length > 0;

    return (
        <div
            className={cn(
                'relative mx-auto mt-10 aspect-[4/5] max-w-6xl overflow-hidden rounded-2xl text-left text-band-fg sm:mt-14 sm:aspect-video lg:aspect-[16/7]',
                hasMedia
                    ? 'dark border border-white/15 bg-[#121218]'
                    : 'border border-band-line bg-band/40 bg-[radial-gradient(70%_80%_at_85%_15%,var(--ember-4),transparent_70%),radial-gradient(60%_70%_at_10%_95%,var(--ember-1),transparent_70%)]',
            )}
        >
            {live ? (
                <LiveFrame live={live} />
            ) : (
                videos.length > 0 && (
                    <VideoPlaylist
                        videos={videos}
                        playing={playing}
                        className="absolute inset-0 size-full object-cover"
                    />
                )
            )}
            {!live && videos.length > 0 && (
                <div className="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/80 via-black/15 to-transparent" />
            )}
            {children}
        </div>
    );
}

/** Full-width video behind the welcome, softly blurred and tinted. */
function BackgroundVideo({
    videos,
    playing,
}: {
    videos: HeroVideo[];
    playing: boolean;
}) {
    return (
        <div aria-hidden="true" className="absolute inset-0 -z-10">
            <VideoPlaylist
                videos={videos}
                playing={playing}
                className="absolute inset-0 size-full scale-110 object-cover blur-[6px]"
            />
            <div className="absolute inset-0 bg-gradient-to-b from-band/70 via-band/45 to-band/90" />
            <div className="absolute inset-0 bg-ember opacity-50 mix-blend-normal" />
        </div>
    );
}

function ThemeCard({ slide }: { slide: HeroSlide }) {
    return (
        <div
            key={slide.title}
            className="max-w-xl animate-in rounded-2xl glass-float px-5 py-4 text-left text-band-fg duration-700 fade-in sm:px-6 sm:py-5"
            aria-live="polite"
        >
            {slide.eyebrow && (
                <p className="font-condensed text-xs tracking-[0.24em] text-band-accent uppercase">
                    {slide.eyebrow}
                </p>
            )}
            <p className="mt-1.5 font-display text-xl leading-tight font-extrabold tracking-tight text-balance sm:text-3xl">
                {slide.title}
            </p>
            {slide.reference && (
                <p className="mt-1.5 text-sm text-band-muted">
                    {slide.reference}
                </p>
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
    className,
}: {
    videos: HeroVideo[];
    playing: boolean;
    className?: string;
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
            className={className}
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
                    <p className="font-display text-3xl font-extrabold tracking-tight sm:text-5xl">
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
            <span className="pointer-events-none absolute top-4 left-4 inline-flex items-center gap-2 rounded-lg bg-[#e5241b] px-3 py-1.5 font-condensed text-xs tracking-[0.2em] text-white">
                <Radio className="size-3.5 motion-safe:animate-pulse" />
                LIVE{live.title ? ` · ${live.title.toUpperCase()}` : ''}
            </span>
        </>
    );
}
