import { Head, Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import type { Album } from '@/modules/gallery/types';
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

            <header className="px-4 pt-14 sm:px-6 sm:pt-20">
                <Reveal className="mx-auto max-w-6xl">
                    <p className="mb-3 text-sm font-semibold tracking-widest text-gold uppercase">
                        Kegiatan
                    </p>
                    <h1 className="font-serif text-4xl leading-tight font-semibold tracking-tight sm:text-6xl">
                        Galeri
                    </h1>
                    <p className="mt-4 max-w-2xl text-lg text-muted-foreground">
                        Momen ibadah dan kegiatan jemaat GGP Elim.
                    </p>
                </Reveal>
            </header>

            <section className="px-4 py-12 sm:px-6">
                <div className="mx-auto max-w-6xl">
                    {albums.data.length === 0 ? (
                        <p className="text-muted-foreground">
                            Belum ada album foto.
                        </p>
                    ) : (
                        <ul className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                            {albums.data.map((album) => (
                                <li key={album.id}>
                                    <Reveal>
                                        <Link
                                            href={album.url}
                                            className="group block"
                                        >
                                            <div className="aspect-[4/3] overflow-hidden rounded-glass border bg-muted">
                                                {album.coverUrl && (
                                                    <img
                                                        src={album.coverUrl}
                                                        alt=""
                                                        loading="lazy"
                                                        className="size-full object-cover transition-transform duration-300 ease-out group-hover:scale-105"
                                                    />
                                                )}
                                            </div>
                                            <h2 className="mt-3 font-serif text-xl font-semibold group-hover:underline">
                                                {album.title}
                                            </h2>
                                            <p className="text-sm text-muted-foreground">
                                                {album.photoCount} foto
                                                {album.eventDateLabel &&
                                                    ` · ${album.eventDateLabel}`}
                                            </p>
                                        </Link>
                                    </Reveal>
                                </li>
                            ))}
                        </ul>
                    )}

                    {albums.last_page > 1 && (
                        <nav
                            aria-label="Halaman album"
                            className="mt-8 flex flex-wrap gap-2"
                        >
                            {albums.links.map((link) =>
                                link.url ? (
                                    <Link
                                        key={link.label}
                                        href={link.url}
                                        className={cn(
                                            'rounded-full border px-4 py-1.5 text-sm',
                                            link.active &&
                                                'bg-primary text-primary-foreground',
                                        )}
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                ) : null,
                            )}
                        </nav>
                    )}
                </div>
            </section>

            <SiteFooter />
        </>
    );
}

GalleryPublicIndex.layout = { nav };
