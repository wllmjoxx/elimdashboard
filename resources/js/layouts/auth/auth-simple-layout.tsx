import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

function Wordmark() {
    return (
        <span className="flex items-center gap-3">
            <span className="flex size-10 items-center justify-center rounded-full border border-band-line">
                <AppLogoIcon className="size-6 fill-flame" />
            </span>
            <span className="flex flex-col text-left leading-none">
                <span className="font-display text-lg font-extrabold tracking-wide">
                    GGP ELIM
                </span>
                <span className="mt-1 font-condensed text-[10px] tracking-[0.24em] text-band-muted">
                    RUMAH KASIH KARUNIA
                </span>
            </span>
        </span>
    );
}

/**
 * Sign-in, registration and password pages: the church's grainy band with
 * the welcome on the left and the form on a floating glass card.
 */
export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="grain relative isolate flex min-h-svh overflow-hidden bg-ember">
            <div
                aria-hidden="true"
                className="absolute bottom-8 left-6 hidden h-28 w-52 border-r border-b border-band-line bg-[linear-gradient(var(--band-line)_1px,transparent_1px),linear-gradient(90deg,var(--band-line)_1px,transparent_1px)] bg-[size:22px_20px] lg:block"
            />

            <div className="relative mx-auto grid w-full max-w-6xl content-center items-center gap-10 px-4 py-10 sm:px-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,28rem)] lg:gap-16 lg:px-10">
                <div className="hidden text-band-fg lg:block">
                    <Link
                        href={home()}
                        aria-label="GGP Elim, ke Beranda"
                        className="inline-block focus-visible:ring-[3px] focus-visible:ring-flame/50 focus-visible:outline-none"
                    >
                        <Wordmark />
                    </Link>
                    <p className="mt-16 font-condensed text-sm tracking-[0.32em] text-band-accent uppercase">
                        Gereja Gerakan Pentakosta · Ciputat
                    </p>
                    <p className="mt-4 font-display text-7xl leading-[0.9] font-black tracking-[-0.045em] [text-shadow:var(--band-glow)] xl:text-8xl">
                        Selamat Datang
                    </p>
                    <p className="mt-4 font-condensed text-2xl tracking-wide">
                        di GGP ELIM Rumah Kasih Karunia
                    </p>
                </div>

                <div className="w-full">
                    <div className="mb-6 flex items-center justify-between text-band-fg">
                        <Link
                            href={home()}
                            aria-label="GGP Elim, ke Beranda"
                            className="lg:hidden"
                        >
                            <Wordmark />
                        </Link>
                        <Link
                            href={home()}
                            className="ml-auto inline-flex items-center gap-1.5 font-condensed text-xs tracking-[0.2em] text-band-muted uppercase hover:text-band-fg"
                        >
                            <ArrowLeft className="size-3.5" />
                            Beranda
                        </Link>
                    </div>

                    <div className="rounded-2xl glass-float p-6 text-foreground sm:p-8">
                        <div className="mb-8 space-y-2">
                            <h1 className="font-display text-3xl leading-tight font-extrabold tracking-tight text-balance">
                                {title}
                            </h1>
                            {description && (
                                <p className="text-sm text-muted-foreground">
                                    {description}
                                </p>
                            )}
                        </div>
                        {children}
                    </div>
                </div>
            </div>
        </div>
    );
}
