export type Album = {
    id: number;
    title: string;
    slug: string;
    description: string | null;
    eventDate: string | null;
    eventDateLabel: string | null;
    url: string;
    coverUrl: string | null;
    photoCount: number | null;
};

export type Photo = {
    id: number;
    url: string;
    thumbnailUrl: string;
    caption: string | null;
    width: number | null;
    height: number | null;
};
