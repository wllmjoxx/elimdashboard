import { Head } from '@inertiajs/react';
import { Fragment } from 'react';
import { nav } from '@/modules/landing/nav';
import { renderSection } from '@/modules/landing/render-section';
import { HomeHighlights } from '@/modules/landing/sections/highlights';
import { SiteFooter } from '@/modules/landing/site-footer';
import type { ScheduleGroup, Section } from '@/modules/landing/types';

type Props = {
    sections: Section[];
    scheduleGroups: ScheduleGroup[];
};

/**
 * Homepage, laid out like gms.church: theme slider, wide image segments
 * set by Admin, and short teasers that lead to the other pages.
 */
export default function Home({ sections, scheduleGroups }: Props) {
    const devotional = sections.find((section) => section.key === 'devotional');
    const hasSchedule = sections.some((section) => section.key === 'schedule');
    // The devotional and schedule teasers share one row, placed where the
    // first of the two comes in the admin-defined order.
    const highlightsAt = sections.findIndex(
        (section) => section.key === 'devotional' || section.key === 'schedule',
    );

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

            {sections.map((section, index) => {
                if (
                    section.key === 'devotional' ||
                    section.key === 'schedule'
                ) {
                    return index === highlightsAt ? (
                        <HomeHighlights
                            key="highlights"
                            devotional={
                                devotional?.key === 'devotional'
                                    ? devotional.content
                                    : undefined
                            }
                            groups={hasSchedule ? scheduleGroups : undefined}
                        />
                    ) : null;
                }

                return (
                    <Fragment key={section.key}>
                        {renderSection(section, scheduleGroups)}
                    </Fragment>
                );
            })}

            <SiteFooter />
        </>
    );
}

Home.layout = { nav };
