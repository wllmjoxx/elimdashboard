import { Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { privacy } from '@/routes/landing';
import { nav } from './nav';

/** Dark page footer shared by every public page. */
export function SiteFooter() {
    return (
        <footer className="relative mt-24 overflow-hidden border-t border-white/10 bg-[#050507] text-white">
            <div className="mx-auto grid max-w-7xl gap-10 px-4 py-16 sm:px-6 md:grid-cols-[1.4fr_1fr_1fr] lg:px-10">
                <div>
                    <div className="flex items-center gap-3">
                        <span className="flex size-11 items-center justify-center rounded-full border border-white/30">
                            <AppLogoIcon className="size-6 fill-flame" />
                        </span>
                        <span className="font-display text-2xl font-extrabold tracking-wide">
                            GGP ELIM
                        </span>
                    </div>
                    <p className="mt-4 max-w-sm text-white/70">
                        Gereja Gerakan Pentakosta Jemaat Elim Ciputat. Rumah
                        Kasih Karunia bagi setiap orang.
                    </p>
                </div>
                <nav aria-label="Tautan cepat">
                    <p className="font-condensed text-xs tracking-[0.24em] text-white/60">
                        JELAJAHI
                    </p>
                    <ul className="mt-4 grid grid-cols-2 gap-x-6 gap-y-2">
                        {nav.map((item) => (
                            <li key={item.href}>
                                <Link
                                    href={item.href}
                                    className="text-white/80 transition-colors hover:text-white"
                                >
                                    {item.label}
                                </Link>
                            </li>
                        ))}
                    </ul>
                </nav>
                <div>
                    <p className="font-condensed text-xs tracking-[0.24em] text-white/60">
                        INFORMASI
                    </p>
                    <ul className="mt-4 grid gap-2">
                        <li>
                            <Link
                                href={privacy()}
                                className="text-white/80 transition-colors hover:text-white"
                            >
                                Kebijakan Privasi
                            </Link>
                        </li>
                    </ul>
                </div>
            </div>
            <div className="border-t border-white/10">
                <p className="mx-auto max-w-7xl px-4 py-6 text-sm text-white/60 sm:px-6 lg:px-10">
                    © {new Date().getFullYear()} Gereja Gerakan Pentakosta
                    Jemaat Elim Ciputat
                </p>
            </div>
        </footer>
    );
}
