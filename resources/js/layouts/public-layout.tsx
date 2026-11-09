import { Link, usePage } from '@inertiajs/react';
import Lenis from 'lenis';
import 'lenis/dist/lenis.css';
import { Menu } from 'lucide-react';
import { useEffect, useState } from 'react';
import type { CSSProperties, ReactNode } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { AppearanceMenu } from '@/components/appearance-menu';
import { Glass } from '@/components/glass';
import { Button } from '@/components/ui/button';
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
 * Random spot for each background glow, picked once per visit. Each glow
 * stays in its own zone so they never pile up: orange left, blue right,
 * purple low and a fainter purple high.
 */
function randomHaze(): CSSProperties {
    const between = (min: number, max: number) =>
        `${Math.round(min + Math.random() * (max - min))}%`;

    return {
        '--haze-flame': `${between(0, 30)} ${between(15, 70)}`,
        '--haze-sky': `${between(70, 100)} ${between(30, 85)}`,
        '--haze-iris': `${between(30, 70)} ${between(75, 100)}`,
        '--haze-iris-soft': `${between(20, 80)} ${between(0, 25)}`,
    } as CSSProperties;
}

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
    const active = internal && path === item.href;

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
    const { name } = usePage().props;

    return (
        <Link
            href={home()}
            className="flex items-center gap-2 rounded-full focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
        >
            <AppLogoIcon className="size-7 fill-flame" />
            <span className="font-serif text-xl font-semibold tracking-tight">
                {name}
            </span>
        </Link>
    );
}

function AuthButtons() {
    const { auth } = usePage().props;

    if (auth.user) {
        return (
            <Button asChild className="rounded-full">
                <Link href={dashboard()}>Dashboard</Link>
            </Button>
        );
    }

    return (
        <>
            <Button asChild variant="glass" className="rounded-full">
                <Link href={login()}>Masuk</Link>
            </Button>
            <Button asChild className="rounded-full">
                <Link href={register()}>Daftar</Link>
            </Button>
        </>
    );
}

/**
 * Layout for public pages (Inertia pages under `<module>/public/...`):
 * soft haze background with randomly placed glows, floating glass navbar
 * with theme switch and Masuk/Daftar buttons. Pages render their own footer.
 */
export default function PublicLayout({
    children,
    nav = [],
}: PublicLayoutProps) {
    // Fixed spots on the server and first render; scattered after mount so
    // hydration matches.
    const [haze, setHaze] = useState<CSSProperties>();

    useEffect(() => setHaze(randomHaze()), []);

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
        <div className="min-h-screen bg-haze text-foreground" style={haze}>
            <a
                href="#konten"
                className="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded-full focus:bg-background focus:px-4 focus:py-2"
            >
                Lewati ke konten
            </a>

            <div className="sticky top-0 z-40 px-4 pt-3 sm:px-6">
                <Glass
                    as="header"
                    className="mx-auto flex h-16 max-w-6xl items-center gap-4 rounded-full px-4 sm:px-6"
                >
                    <Wordmark />

                    <nav
                        aria-label="Menu utama"
                        className="ml-auto hidden items-center gap-1 lg:flex"
                    >
                        {nav.map((item) => (
                            <NavLink
                                key={item.href}
                                item={item}
                                className="rounded-full px-3 py-2 text-sm font-medium text-foreground/80 transition-colors duration-200 ease-out hover:bg-foreground/5 hover:text-foreground"
                                activeClassName="bg-foreground/5 text-foreground"
                            />
                        ))}
                    </nav>

                    <div className="ml-auto flex items-center gap-2 lg:ml-2">
                        <AppearanceMenu />
                        <div className="hidden items-center gap-2 sm:flex">
                            <AuthButtons />
                        </div>

                        <Sheet open={menuOpen} onOpenChange={setMenuOpen}>
                            <SheetTrigger asChild>
                                <Button
                                    variant="glass"
                                    size="icon"
                                    className="rounded-full lg:hidden"
                                    aria-label="Buka menu"
                                >
                                    <Menu />
                                </Button>
                            </SheetTrigger>
                            <SheetContent side="right" className="w-72">
                                <SheetHeader>
                                    <SheetTitle className="font-serif">
                                        Menu
                                    </SheetTitle>
                                    <SheetDescription className="sr-only">
                                        Navigasi situs
                                    </SheetDescription>
                                </SheetHeader>
                                <nav
                                    aria-label="Menu utama"
                                    className="flex flex-col gap-1 px-4"
                                >
                                    {nav.map((item) => (
                                        <NavLink
                                            key={item.href}
                                            item={item}
                                            className="rounded-xl px-3 py-2.5 font-medium hover:bg-foreground/5"
                                            activeClassName="bg-foreground/5 text-flame-strong"
                                            onNavigate={() =>
                                                setMenuOpen(false)
                                            }
                                        />
                                    ))}
                                </nav>
                                <div className="mt-4 flex gap-2 px-4 sm:hidden">
                                    <AuthButtons />
                                </div>
                            </SheetContent>
                        </Sheet>
                    </div>
                </Glass>
            </div>

            <main id="konten">{children}</main>
        </div>
    );
}
