export type Option = { value: string; label: string };

export type PrayerRow = {
    id: number;
    fullName: string;
    age: number | null;
    phone: string | null;
    address: string | null;
    body: string;
    category: string;
    status: 'new' | 'prayed' | 'answered' | 'archived';
    statusLabel: string;
    isMember: boolean;
    prayedBy: string | null;
    sentAt: string;
};

export type Week = {
    start: string;
    label: string;
    previous: string;
    next: string;
    isCurrent: boolean;
    hasNext: boolean;
};

const dateTime = new Intl.DateTimeFormat('id-ID', {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
});

export function formatSentAt(value: string): string {
    return dateTime.format(new Date(value));
}
