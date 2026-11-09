/** A published devotional as public pages receive it. */
export type PublicDevotional = {
    id: number;
    title: string;
    slug: string;
    reference: string;
    verse: string | null;
    excerpt: string;
    date: string;
    dateLabel: string;
    url: string;
    body?: string;
};

/** A devotional row in the admin panel. */
export type DevotionalSummary = {
    id: number;
    title: string;
    slug: string;
    scriptureRef: string;
    publishDate: string;
    status: 'draft' | 'published';
    stateLabel: string;
    author: string | null;
};

export type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
};

const dateFormat = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
});

export function formatDate(value: string): string {
    return dateFormat.format(new Date(`${value}T00:00:00`));
}

/** Splits a plain-text body into paragraphs on blank lines. */
export function paragraphs(body: string): string[] {
    return body
        .split(/\n\s*\n/)
        .map((paragraph) => paragraph.trim())
        .filter(Boolean);
}
