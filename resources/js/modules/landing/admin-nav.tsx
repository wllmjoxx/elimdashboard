import { Link, usePage } from '@inertiajs/react';
import { ExternalLink } from 'lucide-react';
import { cn } from '@/lib/utils';
import { home } from '@/routes';
import { index } from '@/routes/landing';
import { index as media } from '@/routes/landing/media';
import { edit as segments } from '@/routes/landing/segments';
import { index as schedule } from '@/routes/landing/schedule';

const items = [
    { label: 'Tata letak & konten', href: index.url() },
    { label: 'Video & Live', href: media.url() },
    { label: 'Segmen & Galeri', href: segments.url() },
    { label: 'Jadwal ibadah', href: schedule.url() },
];

/** Tabs shared by the landing admin pages. */
export function LandingAdminNav() {
    const { url } = usePage();
    const path = url.split('?')[0];

    return (
        <nav
            aria-label="Pengaturan landing page"
            className="flex flex-wrap items-center gap-2 border-b pb-3"
        >
            {items.map((item) => {
                const active =
                    item.href === index.url()
                        ? path === item.href ||
                          path.startsWith(`${item.href}/sections`)
                        : path.startsWith(item.href);

                return (
                    <Link
                        key={item.href}
                        href={item.href}
                        aria-current={active ? 'page' : undefined}
                        className={cn(
                            'rounded-full px-4 py-1.5 text-sm font-medium transition-colors',
                            active
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                        )}
                    >
                        {item.label}
                    </Link>
                );
            })}
            <a
                href={home.url()}
                target="_blank"
                rel="noopener noreferrer"
                className="ml-auto inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground"
            >
                Lihat situs
                <ExternalLink className="size-3.5" />
            </a>
        </nav>
    );
}
