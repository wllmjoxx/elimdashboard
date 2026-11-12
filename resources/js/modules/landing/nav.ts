import type { PublicNavItem } from '@/layouts/public-layout';
import { index as announcements } from '@/routes/announcements/public';
import { index as devotionals } from '@/routes/devotionals/public';
import { index as events } from '@/routes/events/public';
import { index as gallery } from '@/routes/gallery/public';
import { about, contact, prayer, schedule } from '@/routes/landing';
import { index as testimonies } from '@/routes/testimonies/public';

/**
 * Public menu: one page per item. The logo leads home, so there is no
 * "Beranda" item and the bar stays on one line.
 */
export const nav: PublicNavItem[] = [
    { label: 'Tentang', href: about.url() },
    { label: 'Kegiatan', href: events.url() },
    { label: 'Pengumuman', href: announcements.url() },
    { label: 'Renungan', href: devotionals.url() },
    { label: 'Jadwal', href: schedule.url() },
    { label: 'Galeri', href: gallery.url() },
    { label: 'Kesaksian', href: testimonies.url() },
    { label: 'Form Doa', href: prayer.url() },
    { label: 'Kontak', href: contact.url() },
];
