import { Link, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { privacy } from '@/routes/landing';
import { nav } from './nav';

export function SiteFooter() {
    const { name } = usePage().props;

    return (
        <footer className="mt-8 border-t border-foreground/10 px-4 py-10 sm:px-6">
            <div className="mx-auto flex max-w-6xl flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex items-center gap-2">
                    <AppLogoIcon className="size-6 fill-flame" />
                    <span className="font-serif text-lg font-semibold">
                        {name}
                    </span>
                    <span className="text-sm text-muted-foreground">
                        · Rumah Kasih Karunia
                    </span>
                </div>
                <nav
                    aria-label="Tautan cepat"
                    className="flex flex-wrap gap-x-5 gap-y-2 text-sm"
                >
                    {nav.map((item) => (
                        <Link
                            key={item.href}
                            href={item.href}
                            className="text-muted-foreground hover:text-foreground"
                        >
                            {item.label}
                        </Link>
                    ))}
                    <Link
                        href={privacy()}
                        className="text-muted-foreground hover:text-foreground"
                    >
                        Kebijakan Privasi
                    </Link>
                </nav>
            </div>
            <p className="mx-auto mt-6 max-w-6xl text-sm text-muted-foreground">
                © {new Date().getFullYear()} Gereja Gerakan Pentakosta Jemaat
                Elim Ciputat
            </p>
        </footer>
    );
}
