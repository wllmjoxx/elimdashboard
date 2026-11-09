import { Head, Link, useForm } from '@inertiajs/react';
import { EyeOff, ImageIcon, Plus } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Album } from '@/modules/gallery/types';
import { show, store } from '@/routes/gallery';

type Row = Album & { isActive: boolean; activePhotoCount: number };

type Props = {
    albums: Row[];
    canManage: boolean;
    flash: string | null;
};

function CreateAlbumDialog({
    open,
    onClose,
}: {
    open: boolean;
    onClose: () => void;
}) {
    const form = useForm({ title: '', description: '', event_date: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(store.url(), { onSuccess: onClose });
    };

    return (
        <Dialog open={open} onOpenChange={(value) => !value && onClose()}>
            <DialogContent>
                <form onSubmit={submit} className="grid gap-4">
                    <DialogHeader>
                        <DialogTitle>Album baru</DialogTitle>
                        <DialogDescription>
                            Setelah dibuat, unggah foto ke album ini.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-2">
                        <Label htmlFor="album-title">Judul album</Label>
                        <Input
                            id="album-title"
                            value={form.data.title}
                            onChange={(e) =>
                                form.setData('title', e.target.value)
                            }
                            required
                        />
                        <InputError message={form.errors.title} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="album-date">Tanggal kegiatan</Label>
                        <Input
                            id="album-date"
                            type="date"
                            value={form.data.event_date}
                            onChange={(e) =>
                                form.setData('event_date', e.target.value)
                            }
                        />
                        <InputError message={form.errors.event_date} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="album-description">Keterangan</Label>
                        <Input
                            id="album-description"
                            value={form.data.description}
                            onChange={(e) =>
                                form.setData('description', e.target.value)
                            }
                        />
                        <InputError message={form.errors.description} />
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={onClose}
                        >
                            Batal
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            Buat album
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

/** Gallery admin: list of albums (PRD 4.8). */
export default function GalleryIndex({ albums, canManage, flash }: Props) {
    const [creating, setCreating] = useState(false);

    return (
        <>
            <Head title="Galeri" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Galeri"
                        description="Album foto kegiatan. Pelayan dapat mengunggah foto ke album."
                    />
                    {canManage && (
                        <Button onClick={() => setCreating(true)}>
                            <Plus />
                            Album baru
                        </Button>
                    )}
                </div>

                {flash && (
                    <p
                        role="status"
                        className="rounded-xl border border-green-600/30 bg-green-600/10 px-4 py-3 text-sm"
                    >
                        {flash}
                    </p>
                )}

                {albums.length === 0 ? (
                    <p className="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">
                        Belum ada album.
                    </p>
                ) : (
                    <ul className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        {albums.map((album) => (
                            <li key={album.id}>
                                <Link
                                    href={show(album.id)}
                                    className="group block overflow-hidden rounded-xl border transition-colors hover:border-primary"
                                >
                                    <div className="relative aspect-[4/3] bg-muted">
                                        {album.coverUrl ? (
                                            <img
                                                src={album.coverUrl}
                                                alt=""
                                                className="size-full object-cover"
                                            />
                                        ) : (
                                            <div className="grid size-full place-items-center text-muted-foreground">
                                                <ImageIcon className="size-8" />
                                            </div>
                                        )}
                                        {!album.isActive && (
                                            <Badge
                                                variant="secondary"
                                                className="absolute top-2 left-2"
                                            >
                                                <EyeOff />
                                                Disembunyikan
                                            </Badge>
                                        )}
                                    </div>
                                    <div className="p-4">
                                        <div className="font-medium group-hover:underline">
                                            {album.title}
                                        </div>
                                        <div className="text-sm text-muted-foreground">
                                            {album.photoCount ?? 0} foto
                                            {album.eventDateLabel &&
                                                ` · ${album.eventDateLabel}`}
                                        </div>
                                    </div>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </div>

            <CreateAlbumDialog
                key={creating ? 'open' : 'closed'}
                open={creating}
                onClose={() => setCreating(false)}
            />
        </>
    );
}

GalleryIndex.layout = {
    breadcrumbs: [{ title: 'Galeri', href: '/admin/gallery' }],
};
