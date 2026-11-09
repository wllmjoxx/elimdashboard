export type Option = { value: string; label: string };

export type MemberProfile = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    church: string | null;
    status: string;
    statusLabel: string;
    nickname: string | null;
    photoUrl: string | null;
    birthDate: string | null;
    gender: string | null;
    address: string | null;
    membershipStatus: string;
    membershipLabel: string;
    joinedAt: string | null;
    baptized: boolean;
    privacy: { show_birthday: boolean; show_phone: boolean };
    deletionRequestedAt: string | null;
};

export type ProfileOptions = {
    genders: Option[];
    membershipStatuses: Option[];
};

export const fieldClass =
    'w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

const dateFormat = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
});

export function formatDate(value: string | null): string {
    return value ? dateFormat.format(new Date(`${value}T00:00:00`)) : '-';
}
