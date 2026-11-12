import type { ReactNode } from 'react';
import { sectionIds } from './components';
import { Reveal } from './reveal';
import {
    AboutSection,
    ContactSection,
    DevotionalSection,
    GallerySection,
    MissionSection,
    PrayerSection,
    PrivacySection,
    ScheduleSection,
    TestimoniesSection,
} from './sections/content-sections';
import { EventsSection } from './sections/events';
import { HeroSection } from './sections/hero';
import { PastorSection } from './sections/pastor';
import { SegmentSection } from './sections/segment';
import type { ScheduleGroup, Section } from './types';

/** Full version of a section, as shown on its own page. */
export function renderSection(
    section: Section,
    scheduleGroups: ScheduleGroup[],
) {
    switch (section.key) {
        case 'hero':
            return <HeroSection content={section.content} />;
        case 'segment_1':
        case 'segment_2':
        case 'segment_3':
            return (
                <SegmentSection
                    id={sectionIds[section.key]}
                    content={section.content}
                />
            );
        case 'about':
            return wrap(<AboutSection content={section.content} />);
        case 'mission':
            return wrap(<MissionSection content={section.content} />);
        case 'devotional':
            return wrap(<DevotionalSection content={section.content} />);
        case 'schedule':
            return wrap(
                <ScheduleSection
                    content={section.content}
                    groups={scheduleGroups}
                />,
            );
        case 'prayer':
            return wrap(<PrayerSection content={section.content} />);
        case 'gallery':
            return wrap(<GallerySection content={section.content} />);
        case 'testimonies':
            return wrap(<TestimoniesSection content={section.content} />);
        case 'contact':
            return wrap(<ContactSection content={section.content} />);
        case 'events':
            return wrap(<EventsSection content={section.content} />);
        case 'pastor':
            return <PastorSection content={section.content} />;
        case 'privacy':
            return wrap(<PrivacySection content={section.content} />);
    }
}

function wrap(children: ReactNode) {
    return <Reveal>{children}</Reveal>;
}
