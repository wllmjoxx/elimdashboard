import { Head } from '@inertiajs/react';
import { Fragment } from 'react';
import { PageHero } from '@/modules/landing/components';
import { nav } from '@/modules/landing/nav';
import { renderSection } from '@/modules/landing/render-section';
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

            <PageHero title={title} description={description} />

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
