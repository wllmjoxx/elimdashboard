import { Head, router, useForm } from '@inertiajs/react';
import {
    ArrowDown,
    ArrowUp,
    Eye,
    EyeOff,
    Info,
    Trash2,
    Upload,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import type { Album, Photo } from '@/modules/gallery/types';
import { destroy, update } from '@/routes/gallery';
import photosRoutes from '@/routes/gallery/photos';

type PhotoRow = Photo & { isActive: boolean; canEdit: boolean };

type Props = {
    album: Album & { isActive: boolean };
    photos: PhotoRow[];
    can: { manage: boolean; upload: boolean };
    uploadRules: {
        accept: string;
        maxMb: number;
        minSide: number;
        maxFiles: number;
        compressed: boolean;
        maxSide: number;
    };
    flash: string | null;
};

function AlbumForm({ album }: { album: Props['album'] }) {
    const form = useForm({
        title: album.title,
        description: album.description ?? '',
        event_date: album.eventDate ?? '',
        is_active: album.isActive,
    });

    return (
        <Card>
            <CardHeader>
                <CardTitle>Data album</CardTitle>
            </CardHeader>
            <CardContent>
                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.put(update.url(album.id), {
                            preserveScroll: true,
                        });
                    }}
                    className="grid gap-4 sm:grid-cols-2"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="title">Judul album</Label>
                        <Input
                            id="title"
                            value={form.data.title}
                            onChange={(e) =>
                                form.setData('title', e.target.value)
                            }
                            required
                        />
                        <InputError message={form.errors.title} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="event_date">Tanggal kegiatan</Label>
                        <Input
                            id="event_date"
                            type="date"
                            value={form.data.event_date}
                            onChange={(e) =>
                                form.setData('event_date', e.target.value)
                            }
                        />
                    </div>
                    <div className="grid gap-2 sm:col-span-2">
                        <Label htmlFor="description">Keterangan</Label>
                        <Input
                            id="description"
                            value={form.data.description}
                            onChange={(e) =>
                                form.setData('description', e.target.value)
                            }
                        />
                    </div>
                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="is_active"
                            checked={form.data.is_active}
                            onCheckedChange={(checked) =>
                                form.setData('is_active', checked === true)
                            }
                        />
                        <Label htmlFor="is_active">Tampilkan di situs</Label>
                    </div>
                    <div className="flex flex-wrap items-center justify-end gap-2">
                        <Button
                            type="button"
                            variant="ghost"
                            onClick={() => {
                                if (
                                    window.confirm(
                                        `Hapus album "${album.title}" beserta semua fotonya?`,
                                    )
                                ) {
                                    router.delete(destroy.url(album.id));
                                }
                            }}
                        >
                            <Trash2 />
                            Hapus album
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            Simpan
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}

function UploadForm({
    album,
    rules,
}: {
    album: Props['album'];
    rules: Props['uploadRules'];
}) {
    const form = useForm<{ photos: File[] }>({ photos: [] });
    const [inputKey, setInputKey] = useState(0);
    const tooMany = form.data.photos.length > rules.maxFiles;
    const tooBig = form.data.photos.some(
        (file) => file.size > rules.maxMb * 1024 * 1024,
    );

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(photosRoutes.store.url(album.id), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setInputKey((value) => value + 1);
            },
        });
    };

    const fileErrors = Object.entries(form.errors)
        .filter(([key]) => key.startsWith('photos'))
        .map(([, message]) => message);

    return (
        <Card>
            <CardHeader>
                <CardTitle>Unggah foto</CardTitle>
            </CardHeader>
            <CardContent>
                <form onSubmit={submit} className="grid gap-4">
                    <div className="flex gap-3 rounded-xl border bg-muted/40 p-4 text-sm">
                        <Info className="mt-0.5 size-4 shrink-0 text-sky" />
                        <ul className="list-disc space-y-0.5 pl-4 text-muted-foreground">
                            <li>
                                JPG, PNG, atau WebP; maksimal {rules.maxMb} MB
                                per foto, {rules.maxFiles} foto sekali unggah.
                            </li>
                            <li>Sisi terpendek minimal {rules.minSide} px.</li>
                            <li>
                                {rules.compressed
                                    ? `Foto otomatis diperkecil ke ${rules.maxSide} px dan disimpan sebagai WebP.`
                                    : 'Server ini belum mendukung kompres otomatis; foto disimpan apa adanya.'}
                            </li>
                        </ul>
                    </div>
                    <Input
                        key={inputKey}
                        type="file"
                        multiple
                        accept={rules.accept}
                        onChange={(event) =>
                            form.setData(
                                'photos',
                                Array.from(event.target.files ?? []),
                            )
                        }
                        aria-label="Pilih foto"
                    />
                    {form.data.photos.length > 0 && (
                        <p
                            className={cn(
                                'text-sm',
                                tooMany || tooBig
                                    ? 'text-destructive'
                                    : 'text-muted-foreground',
                            )}
                        >
                            {form.data.photos.length} foto dipilih
                            {tooMany && ` — maksimal ${rules.maxFiles}`}
                            {tooBig &&
                                ` — ada foto lebih dari ${rules.maxMb} MB`}
                        </p>
                    )}
                    {[...new Set(fileErrors)].map((message) => (
                        <InputError key={message} message={message} />
                    ))}
                    <div>
                        <Button
                            type="submit"
                            disabled={
                                form.processing ||
                                form.data.photos.length === 0 ||
                                tooMany ||
                                tooBig
                            }
                        >
                            <Upload />
                            {form.processing ? 'Mengunggah…' : 'Unggah'}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}

function PhotoCard({
    album,
    photo,
    index,
    total,
    canManage,
}: {
    album: Props['album'];
    photo: PhotoRow;
    index: number;
    total: number;
    canManage: boolean;
}) {
    const [caption, setCaption] = useState(photo.caption ?? '');
    const save = (data: Record<string, string | boolean>) =>
        router.put(
            photosRoutes.update.url([album.id, photo.id]),
            { caption, is_active: photo.isActive, ...data },
            { preserveScroll: true },
        );

    return (
        <li
            className={cn(
                'overflow-hidden rounded-xl border',
                !photo.isActive && 'opacity-60',
            )}
        >
            <img
                src={photo.thumbnailUrl}
                alt={photo.caption ?? ''}
                className="aspect-[4/3] w-full bg-muted object-cover"
            />
            <div className="grid gap-2 p-3">
                <Input
                    value={caption}
                    onChange={(e) => setCaption(e.target.value)}
                    onBlur={() =>
                        caption !== (photo.caption ?? '') && save({ caption })
                    }
                    placeholder="Keterangan foto"
                    maxLength={200}
                    disabled={!photo.canEdit}
                    aria-label="Keterangan foto"
                />
                <div className="flex flex-wrap items-center gap-1">
                    {canManage && (
                        <>
                            <Button
                                size="icon"
                                variant="ghost"
                                disabled={index === 0}
                                onClick={() =>
                                    router.post(
                                        photosRoutes.move.url([
                                            album.id,
                                            photo.id,
                                        ]),
                                        { direction: 'up' },
                                        { preserveScroll: true },
                                    )
                                }
                                aria-label="Geser ke depan"
                            >
                                <ArrowUp />
                            </Button>
                            <Button
                                size="icon"
                                variant="ghost"
                                disabled={index === total - 1}
                                onClick={() =>
                                    router.post(
                                        photosRoutes.move.url([
                                            album.id,
                                            photo.id,
                                        ]),
                                        { direction: 'down' },
                                        { preserveScroll: true },
                                    )
                                }
                                aria-label="Geser ke belakang"
                            >
                                <ArrowDown />
                            </Button>
                        </>
                    )}
                    {photo.canEdit && (
                        <>
                            <Button
                                size="icon"
                                variant="ghost"
                                onClick={() =>
                                    save({ is_active: !photo.isActive })
                                }
                                aria-label={
                                    photo.isActive
                                        ? 'Sembunyikan foto'
                                        : 'Tampilkan foto'
                                }
                            >
                                {photo.isActive ? <Eye /> : <EyeOff />}
                            </Button>
                            <Button
                                size="icon"
                                variant="ghost"
                                className="ml-auto"
                                onClick={() => {
                                    if (window.confirm('Hapus foto ini?')) {
                                        router.delete(
                                            photosRoutes.destroy.url([
                                                album.id,
                                                photo.id,
                                            ]),
                                            { preserveScroll: true },
                                        );
                                    }
                                }}
                                aria-label="Hapus foto"
                            >
                                <Trash2 />
                            </Button>
                        </>
                    )}
                </div>
            </div>
        </li>
    );
}

/** One album: details, upload and photos (PRD 4.8). */
export default function GalleryShow({
    album,
    photos,
    can,
    uploadRules,
    flash,
}: Props) {
    return (
        <>
            <Head title={album.title} />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <Heading
                    title={album.title}
                    description={`${photos.length} foto${album.eventDateLabel ? ` · ${album.eventDateLabel}` : ''}`}
                />

                {flash && (
                    <p
                        role="status"
                        className="rounded-xl border border-green-600/30 bg-green-600/10 px-4 py-3 text-sm"
                    >
                        {flash}
                    </p>
                )}

                {can.manage && <AlbumForm album={album} />}
                {can.upload && <UploadForm album={album} rules={uploadRules} />}

                {photos.length === 0 ? (
                    <p className="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">
                        Album ini belum berisi foto.
                    </p>
                ) : (
                    <ul className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        {photos.map((photo, index) => (
                            <PhotoCard
                                key={photo.id}
                                album={album}
                                photo={photo}
                                index={index}
                                total={photos.length}
                                canManage={can.manage}
                            />
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}

GalleryShow.layout = {
    breadcrumbs: [{ title: 'Galeri', href: '/admin/gallery' }],
};
