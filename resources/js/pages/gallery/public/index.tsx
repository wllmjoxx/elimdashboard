import { Head, Link } from '@inertiajs/react';
import type { Album } from '@/modules/gallery/types';
import { PageHero, Pagination } from '@/modules/landing/components';
import { nav } from '@/modules/landing/nav';
import { Reveal } from '@/modules/landing/reveal';
import { SiteFooter } from '@/modules/landing/site-footer';

type Props = {
    albums: {
        data: Album[];
        links: { url: string | null; label: string; active: boolean }[];
        last_page: number;
    };
};

/** Public gallery: albums with photos (PRD 4.3). */
export default function GalleryPublicIndex({ albums }: Props) {
    return (
        <>
            <Head title="Galeri" />

            <PageHero
                eyebrow="Kegiatan"
                title="Galeri"
                description="Momen ibadah dan kegiatan jemaat GGP Elim."
            />

            <section className="px-4 py-16 sm:px-6 sm:py-24 lg:px-10">
                <div className="mx-auto max-w-7xl">
                    {albums.data.length === 0 ? (
                        <p className="text-lg text-muted-foreground">
                            Belum ada album foto.
                        </p>
                    ) : (
                        <ul className="grid gap-x-5 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
                            {albums.data.map((album) => (
                                <li key={album.id}>
                                    <Reveal>
                                        <Link
                                            href={album.url}
                                            className="group block"
                                        >
                                            <div className="aspect-[4/3] overflow-hidden border border-foreground/10 bg-muted">
                                                {album.coverUrl && (
                                                    <img
                                                        src={album.coverUrl}
                                                        alt=""
                                                        loading="lazy"
                                                        className="size-full object-cover transition-transform duration-300 ease-out group-hover:scale-105"
                                                    />
                                                )}
                                            </div>
                                            <p className="mt-4 font-condensed text-xs tracking-[0.24em] text-flame-strong uppercase">
                                                {album.photoCount} foto
                                                {album.eventDateLabel &&
                                                    ` · ${album.eventDateLabel}`}
                                            </p>
                                            <h2 className="mt-2 font-display text-2xl leading-tight font-extrabold tracking-tight group-hover:underline">
                                                {album.title}
                                            </h2>
                                        </Link>
                                    </Reveal>
                                </li>
                            ))}
                        </ul>
                    )}

                    {albums.last_page > 1 && (
                        <Pagination
                            label="Halaman album"
                            links={albums.links}
                        />
                    )}
                </div>
            </section>

            <SiteFooter />
        </>
    );
}

GalleryPublicIndex.layout = { nav };
