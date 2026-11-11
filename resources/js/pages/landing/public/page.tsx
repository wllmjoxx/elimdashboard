import { Head } from '@inertiajs/react';
import { Fragment } from 'react';
import { nav } from '@/modules/landing/nav';
import { renderSection } from '@/modules/landing/render-section';
import { Reveal } from '@/modules/landing/reveal';
import { SiteFooter } from '@/modules/landing/site-footer';
import type { ScheduleGroup, Section } from '@/modules/landing/types';

type Props = {
    page: string;
    title: string;
    description: string;
    sections: Section[];
    scheduleGroups: ScheduleGroup[];
};

/**
 * One page per menu item (Tentang, Renungan, Jadwal, Form Doa, Contact):
 * a page title followed by that page's sections.
 */
export default function LandingPage({
    title,
    description,
    sections,
    scheduleGroups,
}: Props) {
    return (
        <>
            <Head title={title} />

            <header className="px-4 pt-14 sm:px-6 sm:pt-20">
                <Reveal className="mx-auto max-w-6xl">
                    <p className="mb-3 text-sm font-semibold tracking-widest text-gold uppercase">
                        GGP Elim Ciputat
                    </p>
                    <h1 className="font-serif text-4xl leading-tight font-semibold tracking-tight text-balance sm:text-6xl">
                        {title}
                    </h1>
                    <p className="mt-4 max-w-2xl text-lg text-pretty text-muted-foreground">
                        {description}
                    </p>
                </Reveal>
            </header>

            {sections.map((section) => (
                <Fragment key={section.key}>
                    {renderSection(section, scheduleGroups)}
                </Fragment>
            ))}

            <SiteFooter />
        </>
    );
}

LandingPage.layout = { nav };
