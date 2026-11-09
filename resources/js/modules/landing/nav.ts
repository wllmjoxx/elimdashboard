import type { PublicNavItem } from '@/layouts/public-layout';
import { home } from '@/routes';
import { index as devotionals } from '@/routes/devotionals/public';
import { about, contact, prayer, schedule } from '@/routes/landing';

/** Public menu, following ggpelim.com: one page per item. */
export const nav: PublicNavItem[] = [
    { label: 'Home', href: home.url() },
    { label: 'Tentang GGP Elim', href: about.url() },
    { label: 'Renungan Harian', href: devotionals.url() },
    { label: 'Jadwal', href: schedule.url() },
    { label: 'Form Doa', href: prayer.url() },
    { label: 'Contact', href: contact.url() },
];
