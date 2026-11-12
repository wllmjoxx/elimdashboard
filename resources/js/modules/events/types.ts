export type EventState = 'ongoing' | 'upcoming' | 'past';

/** An event as the public pages and the admin list receive it. */
export type PublicEvent = {
    id: number;
    title: string;
    slug: string;
    summary: string | null;
    location: string | null;
    startsAt: string;
    endsAt: string | null;
    dateLabel: string;
    timeLabel: string;
    state: EventState;
    stateLabel: string;
    imageUrl: string | null;
    registrationUrl: string | null;
    url: string;
    body?: string | null;
};
