import type { PublicEvent } from '@/modules/events/types';

export type Cta = { label: string; href: string };

export type HeroSlide = {
    eyebrow?: string;
    title: string;
    text?: string;
    verse?: string;
    reference?: string;
};

export type HeroVideo = {
    id: number;
    title: string | null;
    url: string;
    poster: string | null;
    type: string;
};

export type HeroLive = {
    is_live: boolean;
    title: string | null;
    url: string | null;
    embed_url: string | null;
};

export type HeroContent = {
    headline?: string | null;
    subheadline?: string | null;
    slides: HeroSlide[];
    primary_cta?: Cta;
    secondary_cta?: Cta;
    videos?: HeroVideo[];
    live?: HeroLive;
    video_mode?: 'frame' | 'background';
};

export type AboutContent = {
    title: string;
    paragraphs: string[];
    milestones?: { year: string; text: string }[];
};

export type EventsContent = {
    title: string;
    subtitle?: string | null;
    items: PublicEvent[];
    url: string;
};

export type PastorContent = {
    label: string;
    name: string;
    paragraphs: string[];
    more_label?: string | null;
    more_href?: string | null;
    social_name?: string | null;
    instagram?: string | null;
    youtube?: string | null;
    facebook?: string | null;
    photo_url?: string | null;
};

export type PrivacyContent = {
    title: string;
    effective?: string | null;
    intro?: string | null;
    points: { heading: string; body: string }[];
};

export type MissionContent = {
    title: string;
    text: string;
    verse?: string;
    reference?: string;
};

export type DevotionalContent = {
    label: string;
    title: string;
    reference: string;
    verse: string;
    excerpt: string;
    /** Page of the published devotional; absent for the sample. */
    url?: string;
    is_sample?: boolean;
};

export type ScheduleContent = { title: string; subtitle?: string };

export type PrayerContent = {
    title: string;
    text: string;
    verse: string;
    reference: string;
};

export type GalleryContent = {
    title: string;
    items: { caption: string; image?: string; full?: string }[];
    layout?: 'grid' | 'carousel' | 'masonry';
    /** Album shown (Gallery module), its page and the album list. */
    album?: string | null;
    url?: string | null;
    index_url?: string;
    is_sample?: boolean;
};

export type TestimonyItem = {
    id: number;
    title: string;
    body: string;
    excerpt: string;
    category: string;
    name: string;
};

export type TestimoniesContent = {
    title: string;
    text: string;
    /** Approved testimonies chosen for Home (Testimonies module). */
    items?: TestimonyItem[];
    url?: string;
};

export type ContactContent = {
    title: string;
    address: string;
    whatsapp: string;
    hours: string;
    maps_query: string;
};

export type ScheduleGroup = {
    category: string;
    label: string;
    items: {
        id: number;
        name: string;
        day: string;
        time: string;
        location: string | null;
    }[];
};

export type SegmentContent = {
    eyebrow?: string | null;
    title: string;
    text?: string | null;
    button_label?: string | null;
    button_href?: string | null;
    align: 'left' | 'center' | 'right';
    image_url: string | null;
};

export type SectionContentMap = {
    hero: HeroContent;
    about: AboutContent;
    mission: MissionContent;
    devotional: DevotionalContent;
    schedule: ScheduleContent;
    prayer: PrayerContent;
    gallery: GalleryContent;
    testimonies: TestimoniesContent;
    contact: ContactContent;
    privacy: PrivacyContent;
    pastor: PastorContent;
    events: EventsContent;
    segment_1: SegmentContent;
    segment_2: SegmentContent;
    segment_3: SegmentContent;
};

export type SectionKey = keyof SectionContentMap;

export type Section = {
    [K in SectionKey]: { key: K; content: SectionContentMap[K] };
}[SectionKey];
