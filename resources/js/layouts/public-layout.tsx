import { Link, usePage } from '@inertiajs/react';
import Lenis from 'lenis';
import 'lenis/dist/lenis.css';
import { Menu } from 'lucide-react';
import { useEffect, useState } from 'react';
import type { ReactNode } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { AppearanceMenu } from '@/components/appearance-menu';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { cn } from '@/lib/utils';
import { dashboard, home, login, register } from '@/routes';

export type PublicNavItem = { label: string; href: string };

type PublicLayoutProps = {
    children: ReactNode;
    /** Navbar links, set per page via `Page.layout = { nav: [...] }`. */
    nav?: PublicNavItem[];
};

/**
 * Buttons of the public site, colored by the band they sit on: solid and
 * outline are square-cornered; glass is the floating, slightly rounded one.
 */
export const publicButton = {
    solid: 'inline-flex items-center justify-center gap-2 bg-band-solid px-5 py-3 text-[13px] font-semibold tracking-[0.12em] text-band-solid-fg uppercase transition-opacity duration-200 hover:opacity-85 focus-visible:ring-[3px] focus-visible:ring-flame/50 focus-visible:outline-none',
    outline:
        'inline-flex items-center justify-center gap-2 border border-band-line px-5 py-3 text-[13px] font-semibold tracking-[0.12em] text-band-fg uppercase transition-colors duration-200 hover:border-band-fg hover:bg-band-fg/10 focus-visible:ring-[3px] focus-visible:ring-flame/50 focus-visible:outline-none',
    glass: 'glass-float inline-flex items-center justify-center gap-2 rounded-lg px-5 py-3 text-[13px] font-semibold tracking-[0.12em] text-band-fg uppercase transition-colors duration-200 hover:bg-band-fg/10 focus-visible:ring-[3px] focus-visible:ring-flame/50 focus-visible:outline-none',
};

/**
 * A navbar link: Inertia navigation for pages of this site, a normal link
 * for anything else. The current page is marked for screen readers too.
 */
function NavLink({
    item,
    className,
    activeClassName,
    onNavigate,
}: {
    item: PublicNavItem;
    className: string;
    activeClassName: string;
    onNavigate?: () => void;
}) {
    const { url } = usePage();
    const path = url.split(/[?#]/)[0];
    const internal = item.href.startsWith('/');
    const active =
        internal &&
        (path === item.href ||
            (item.href !== home.url() && path.startsWith(`${item.href}/`)));

    if (!internal) {
        return (
            <a href={item.href} className={className} onClick={onNavigate}>
                {item.label}
            </a>
        );
    }

    return (
        <Link
            href={item.href}
            aria-current={active ? 'page' : undefined}
            className={cn(className, active && activeClassName)}
            onClick={onNavigate}
        >
            {item.label}
        </Link>
    );
}

function Wordmark() {
    return (
        <Link
            href={home()}
            className="flex shrink-0 items-center gap-3 focus-visible:ring-[3px] focus-visible:ring-flame/50 focus-visible:outline-none"
            aria-label="GGP Elim, ke Beranda"
        >
            <span className="flex size-10 items-center justify-center rounded-full border border-band-line">
                <AppLogoIcon className="size-6 fill-flame" />
            </span>
            <span className="flex flex-col leading-none">
                <span className="font-display text-lg font-extrabold tracking-wide">
                    GGP ELIM
                </span>
                <span className="mt-1 font-condensed text-[10px] tracking-[0.24em] text-band-muted">
                    RUMAH KASIH KARUNIA
                </span>
            </span>
        </Link>
    );
}

function AuthButtons({ className }: { className?: string }) {
    const { auth } = usePage().props;

    if (auth.user) {
        return (
            <Link
                href={dashboard()}
                className={cn(publicButton.solid, className)}
            >
                Dashboard
            </Link>
        );
    }

    return (
        <>
            <Link
                href={register()}
                className={cn(publicButton.outline, className)}
            >
                Daftar
            </Link>
            <Link href={login()} className={cn(publicButton.solid, className)}>
                Masuk
            </Link>
        </>
    );
}

/**
 * Layout for public pages (Inertia pages under `<module>/public/...`): a
 * floating glass header over each page's top band, in the style of the
 * church's event slides (heavy grotesk, uppercase menu, square buttons).
 * Everything follows the light or dark theme; pages render their own
 * footer and start with a band that leaves room for the header.
 */
export default function PublicLayout({
    children,
    nav = [],
}: PublicLayoutProps) {
    // Smooth, eased scrolling for wheel, touchpad and in-page anchors.
    // Skipped for visitors who ask for reduced motion.
    useEffect(() => {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }

        const lenis = new Lenis({ autoRaf: true, anchors: true });

        return () => lenis.destroy();
    }, []);

    // The layout stays mounted between pages, so close the mobile menu
    // after a link is chosen.
    const [menuOpen, setMenuOpen] = useState(false);

    return (
        <div className="relative isolate min-h-screen bg-background text-foreground">
            <a
                href="#konten"
                className="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:bg-background focus:px-4 focus:py-2 focus:text-foreground"
            >
                Lewati ke konten
            </a>

            {/* Zero-height sticky holder: the bar floats over the band below. */}
            <div className="sticky top-0 z-40 h-0">
                <header className="mx-3 mt-3 flex h-16 items-center gap-6 rounded-2xl glass-float px-4 text-band-fg sm:mx-6 sm:mt-5 sm:h-[72px] sm:px-5 lg:mx-auto lg:max-w-[calc(80rem+2.5rem)]">
                    <Wordmark />

                    <nav
                        aria-label="Menu utama"
                        className="mx-auto hidden items-center gap-5 xl:flex 2xl:gap-7"
                    >
                        {nav.map((item) => (
                            <NavLink
                                key={item.href}
                                item={item}
                                className="border-b-2 border-transparent py-2 text-[13px] font-semibold tracking-[0.1em] whitespace-nowrap text-band-muted uppercase transition-colors duration-200 hover:text-band-fg"
                                activeClassName="border-flame text-band-fg"
                            />
                        ))}
                    </nav>

                    <div className="ml-auto flex items-center gap-3 xl:ml-0">
                        <AppearanceMenu className="rounded-lg border border-band-line bg-transparent text-band-fg shadow-none hover:bg-band-fg/10 dark:hover:bg-band-fg/10" />
                        <div className="hidden items-center gap-3 md:flex">
                            <AuthButtons />
                        </div>

                        <Sheet open={menuOpen} onOpenChange={setMenuOpen}>
                            <SheetTrigger asChild>
                                <button
                                    type="button"
                                    className="flex size-11 items-center justify-center rounded-lg border border-band-line text-band-fg transition-colors hover:bg-band-fg/10 xl:hidden"
                                    aria-label="Buka menu"
                                >
                                    <Menu className="size-5" />
                                </button>
                            </SheetTrigger>
                            <SheetContent
                                side="right"
                                className="w-80 bg-background text-foreground"
                            >
                                <SheetHeader>
                                    <SheetTitle className="font-condensed tracking-[0.24em] text-muted-foreground">
                                        MENU
                                    </SheetTitle>
                                    <SheetDescription className="sr-only">
                                        Navigasi situs
                                    </SheetDescription>
                                </SheetHeader>
                                <nav
                                    aria-label="Menu utama"
                                    className="flex flex-col px-4"
                                >
                                    {nav.map((item) => (
                                        <NavLink
                                            key={item.href}
                                            item={item}
                                            className="border-b border-foreground/10 py-4 font-display text-2xl font-extrabold tracking-tight"
                                            activeClassName="text-flame"
                                            onNavigate={() =>
                                                setMenuOpen(false)
                                            }
                                        />
                                    ))}
                                </nav>
                                <div className="mt-6 grid gap-3 px-4 md:hidden">
                                    <AuthButtons className="w-full" />
                                </div>
                            </SheetContent>
                        </Sheet>
                    </div>
                </header>
            </div>

            <main id="konten">{children}</main>
        </div>
    );
}
