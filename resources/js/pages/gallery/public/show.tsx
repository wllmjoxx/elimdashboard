import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, ChevronLeft, ChevronRight, X } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import type { Album, Photo } from '@/modules/gallery/types';
import { nav } from '@/modules/landing/nav';
import { Reveal } from '@/modules/landing/reveal';
import { SiteFooter } from '@/modules/landing/site-footer';
import { index } from '@/routes/gallery/public';

type Props = {
    album: Album;
    photos: Photo[];
};

/** One album with a simple lightbox. */
export default function GalleryPublicShow({ album, photos }: Props) {
    const [open, setOpen] = useState<number | null>(null);
    const current = open === null ? null : photos[open];

    const go = useCallback(
        (step: number) =>
            setOpen((value) =>
                value === null
                    ? null
                    : (value + step + photos.length) % photos.length,
            ),
        [photos.length],
    );

    useEffect(() => {
        if (open === null) {
            return;
        }

        const onKey = (event: KeyboardEvent) => {
            if (event.key === 'ArrowRight') {
                go(1);
            }

            if (event.key === 'ArrowLeft') {
                go(-1);
            }
        };

        window.addEventListener('keydown', onKey);

        return () => window.removeEventListener('keydown', onKey);
    }, [open, go]);

    return (
        <>
            <Head title={album.title} />

            <header className="px-4 pt-14 sm:px-6 sm:pt-20">
                <Reveal className="mx-auto max-w-6xl">
                    <Link
                        href={index()}
                        className="inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" />
                        Semua album
                    </Link>
                    <h1 className="mt-4 font-serif text-4xl leading-tight font-semibold tracking-tight sm:text-5xl">
                        {album.title}
                    </h1>
                    <p className="mt-2 text-muted-foreground">
                        {album.photoCount} foto
                        {album.eventDateLabel && ` · ${album.eventDateLabel}`}
                    </p>
                    {album.description && (
                        <p className="mt-3 max-w-2xl text-lg text-muted-foreground">
                            {album.description}
                        </p>
                    )}
                </Reveal>
            </header>

            <section className="px-4 py-10 sm:px-6">
                <ul className="mx-auto max-w-6xl columns-2 gap-4 md:columns-3 [&>li]:mb-4">
                    {photos.map((photo, position) => (
                        <li key={photo.id} className="break-inside-avoid">
                            <button
                                type="button"
                                onClick={() => setOpen(position)}
                                className="block w-full overflow-hidden rounded-glass border focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                                aria-label={
                                    photo.caption
                                        ? `Buka foto: ${photo.caption}`
                                        : `Buka foto ${position + 1}`
                                }
                            >
                                <img
                                    src={photo.thumbnailUrl}
                                    alt={photo.caption ?? ''}
                                    loading="lazy"
                                    decoding="async"
                                    className="w-full"
                                />
                            </button>
                            {photo.caption && (
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {photo.caption}
                                </p>
                            )}
                        </li>
                    ))}
                </ul>
            </section>

            <Dialog
                open={current !== null}
                onOpenChange={(value) => !value && setOpen(null)}
            >
                <DialogContent className="max-w-5xl border-none bg-black/95 p-2 sm:p-4 [&>button:last-child]:hidden">
                    <DialogTitle className="sr-only">{album.title}</DialogTitle>
                    <DialogDescription className="sr-only">
                        {current?.caption ?? 'Foto'}
                    </DialogDescription>
                    {current && (
                        <figure className="relative">
                            <img
                                src={current.url}
                                alt={current.caption ?? ''}
                                className="mx-auto max-h-[80vh] w-auto rounded-md object-contain"
                            />
                            {current.caption && (
                                <figcaption className="mt-2 text-center text-sm text-white/80">
                                    {current.caption}
                                </figcaption>
                            )}
                            <Button
                                size="icon"
                                variant="glass"
                                className="absolute top-2 right-2 rounded-full"
                                onClick={() => setOpen(null)}
                                aria-label="Tutup"
                            >
                                <X />
                            </Button>
                            {photos.length > 1 && (
                                <>
                                    <Button
                                        size="icon"
                                        variant="glass"
                                        className="absolute top-1/2 left-2 -translate-y-1/2 rounded-full"
                                        onClick={() => go(-1)}
                                        aria-label="Foto sebelumnya"
                                    >
                                        <ChevronLeft />
                                    </Button>
                                    <Button
                                        size="icon"
                                        variant="glass"
                                        className="absolute top-1/2 right-2 -translate-y-1/2 rounded-full"
                                        onClick={() => go(1)}
                                        aria-label="Foto berikutnya"
                                    >
                                        <ChevronRight />
                                    </Button>
                                </>
                            )}
                        </figure>
                    )}
                </DialogContent>
            </Dialog>

            <SiteFooter />
        </>
    );
}

GalleryPublicShow.layout = { nav };
