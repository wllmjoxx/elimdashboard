import type { PublicNavItem } from '@/layouts/public-layout';
import { home } from '@/routes';
import { index as devotionals } from '@/routes/devotionals/public';
import { index as gallery } from '@/routes/gallery/public';
import { about, contact, prayer, schedule } from '@/routes/landing';
import { index as testimonies } from '@/routes/testimonies/public';

/** Public menu: one page per item. */
export const nav: PublicNavItem[] = [
    { label: 'Beranda', href: home.url() },
    { label: 'Tentang', href: about.url() },
    { label: 'Renungan', href: devotionals.url() },
    { label: 'Jadwal', href: schedule.url() },
    { label: 'Galeri', href: gallery.url() },
    { label: 'Kesaksian', href: testimonies.url() },
    { label: 'Form Doa', href: prayer.url() },
    { label: 'Kontak', href: contact.url() },
];
