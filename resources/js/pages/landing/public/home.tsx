import { Head, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import type { PublicNavItem } from '@/layouts/public-layout';
import {
    AboutSection,
    ContactSection,
    DevotionalSection,
    GallerySection,
    MissionSection,
    PrayerSection,
    ScheduleSection,
    TestimoniesSection,
} from '@/modules/landing/sections/content-sections';
import { HeroSection } from '@/modules/landing/sections/hero';
import type { ScheduleGroup, Section } from '@/modules/landing/types';

const nav: PublicNavItem[] = [
    { label: 'Home', href: '#beranda' },
    { label: 'Tentang GGP Elim', href: '#tentang' },
    { label: 'Renungan Harian', href: '#renungan' },
    { label: 'Jadwal', href: '#jadwal' },
    { label: 'Form Doa', href: '#doa' },
    { label: 'Contact', href: '#kontak' },
];

type Props = {
    sections: Section[];
    scheduleGroups: ScheduleGroup[];
};

function renderSection(section: Section, scheduleGroups: ScheduleGroup[]) {
    switch (section.key) {
        case 'hero':
            return <HeroSection content={section.content} />;
        case 'about':
            return <AboutSection content={section.content} />;
        case 'mission':
            return <MissionSection content={section.content} />;
        case 'devotional':
            return <DevotionalSection content={section.content} />;
        case 'schedule':
            return (
                <ScheduleSection
                    content={section.content}
                    groups={scheduleGroups}
                />
            );
        case 'prayer':
            return <PrayerSection content={section.content} />;
        case 'gallery':
            return <GallerySection content={section.content} />;
        case 'testimonies':
            return <TestimoniesSection content={section.content} />;
        case 'contact':
            return <ContactSection content={section.content} />;
    }
}

function SiteFooter() {
    const { name } = usePage().props;

    return (
        <footer className="border-t border-foreground/10 px-4 py-10 sm:px-6">
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
                        <a
                            key={item.href}
                            href={item.href}
                            className="text-muted-foreground hover:text-foreground"
                        >
                            {item.label}
                        </a>
                    ))}
                </nav>
            </div>
            <p className="mx-auto mt-6 max-w-6xl text-sm text-muted-foreground">
                © {new Date().getFullYear()} Gereja Gerakan Pentakosta Jemaat
                Elim Ciputat
            </p>
        </footer>
    );
}

export default function Home({ sections, scheduleGroups }: Props) {
    return (
        <>
            <Head title="Beranda">
                <meta
                    name="description"
                    content="GGP Elim Ciputat (Rumah Kasih Karunia): jadwal ibadah, renungan harian, form doa, dan kontak Gereja Gerakan Pentakosta Jemaat Elim di Ciputat, Tangerang Selatan."
                />
                <meta property="og:title" content="GGP Elim Ciputat" />
                <meta
                    property="og:description"
                    content="Rumah Kasih Karunia di Ciputat, Tangerang Selatan."
                />
                <meta property="og:type" content="website" />
            </Head>

            {sections.map((section) => (
                <div key={section.key}>
                    {renderSection(section, scheduleGroups)}
                </div>
            ))}

            <SiteFooter />
        </>
    );
}

Home.layout = { nav };
