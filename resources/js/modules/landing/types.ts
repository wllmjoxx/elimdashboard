export type Cta = { label: string; href: string };

export type HeroSlide = {
    eyebrow?: string;
    title: string;
    text?: string;
    verse?: string;
    reference?: string;
};

export type HeroContent = {
    slides: HeroSlide[];
    primary_cta?: Cta;
    secondary_cta?: Cta;
};

export type AboutContent = {
    title: string;
    paragraphs: string[];
    milestones?: { year: string; text: string }[];
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
    items: { caption: string; image?: string }[];
    is_sample?: boolean;
};

export type TestimoniesContent = { title: string; text: string };

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
};

export type SectionKey = keyof SectionContentMap;

export type Section = {
    [K in SectionKey]: { key: K; content: SectionContentMap[K] };
}[SectionKey];
