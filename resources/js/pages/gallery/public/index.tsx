import { Head, Link } from '@inertiajs/react';
import { publicButton } from '@/layouts/public-layout';
import type { Album } from '@/modules/gallery/types';
import { PageHero, Pagination } from '@/modules/landing/components';
import { nav } from '@/modules/landing/nav';
import { Reveal } from '@/modules/landing/reveal';
import { SiteFooter } from '@/modules/landing/site-footer';

type InstagramPost = {
    id: string;
    caption: string | null;
    image: string;
    permalink: string;
    type: string;
};

type Props = {
    albums: {
        data: Album[];
        links: { url: string | null; label: string; active: boolean }[];
        last_page: number;
    } | null;
    instagram: {
        username: string;
        profileUrl: string;
        posts: InstagramPost[];
    } | null;
};

/**
 * Public gallery (PRD 4.3): albums uploaded by the team, or the church's
 * latest Instagram posts when Admin connected the account.
 */
export default function GalleryPublicIndex({ albums, instagram }: Props) {
    if (instagram) {
        return <InstagramGallery {...instagram} />;
    }

    if (!albums) {
        return null;
    }

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

function InstagramGallery({
    username,
    profileUrl,
    posts,
}: NonNullable<Props['instagram']>) {
    return (
        <>
            <Head title="Galeri" />

            <PageHero
                eyebrow={`Instagram · @${username}`}
                title="Galeri"
                description="Momen ibadah dan kegiatan jemaat GGP Elim, langsung dari Instagram kami."
            >
                <a
                    href={profileUrl}
                    target="_blank"
                    rel="noopener noreferrer"
                    className={`${publicButton.solid} mt-8`}
                >
                    Ikuti @{username}
                </a>
            </PageHero>

            <section className="px-4 py-16 sm:px-6 sm:py-24 lg:px-10">
                <div className="mx-auto max-w-7xl">
                    {posts.length === 0 ? (
                        <p className="text-lg text-muted-foreground">
                            Postingan Instagram belum bisa dimuat. Silakan
                            kunjungi akun kami langsung.
                        </p>
                    ) : (
                        <ul className="grid grid-cols-2 gap-2 sm:gap-3 md:grid-cols-3 lg:grid-cols-4">
                            {posts.map((post) => (
                                <li key={post.id}>
                                    <a
                                        href={post.permalink}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="group relative block aspect-square overflow-hidden rounded-lg border border-foreground/10 bg-muted focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                                        aria-label={
                                            post.caption
                                                ? `Buka di Instagram: ${post.caption}`
                                                : 'Buka postingan di Instagram'
                                        }
                                    >
                                        <img
                                            src={post.image}
                                            alt={post.caption ?? ''}
                                            loading="lazy"
                                            decoding="async"
                                            className="size-full object-cover transition-transform duration-300 ease-out group-hover:scale-105"
                                        />
                                        {post.type === 'VIDEO' && (
                                            <span className="absolute top-2 right-2 rounded-lg glass-float px-2 py-1 font-condensed text-[11px] tracking-[0.2em] text-band-fg">
                                                VIDEO
                                            </span>
                                        )}
                                    </a>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </section>

            <SiteFooter />
        </>
    );
}

GalleryPublicIndex.layout = { nav };
