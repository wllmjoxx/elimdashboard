import { Head, router, useForm } from '@inertiajs/react';
import {
    ArrowDown,
    ArrowUp,
    Eye,
    EyeOff,
    Film,
    Info,
    Radio,
    Trash2,
    Upload,
} from 'lucide-react';
import { useRef, useState } from 'react';
import type { ChangeEvent, FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { LandingAdminNav } from '@/modules/landing/admin-nav';
import { update as liveUpdate } from '@/routes/landing/live';
import { mode as modeUpdate } from '@/routes/landing/media';
import {
    destroy,
    order,
    store,
    update as videoUpdate,
} from '@/routes/landing/videos';

type Video = {
    id: number;
    title: string | null;
    url: string;
    posterUrl: string | null;
    sizeMb: number;
    isActive: boolean;
};

type Live = {
    url: string;
    title: string;
    isLive: boolean;
    isOn: boolean;
    embeddable: boolean;
    startedAt: string | null;
    autoOffHours: number;
};

type Props = {
    videos: Video[];
    live: Live;
    rules: { maxMb: number; maxCount: number; recommended: string };
    flash: string | null;
    videoMode: 'frame' | 'background';
};

/** Admin › Landing Page › Video & Live (first homepage segment). */
export default function LandingMedia({
    videos,
    live,
    rules,
    flash,
    videoMode,
}: Props) {
    return (
        <>
            <Head title="Video & Live" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <LandingAdminNav />
                <Heading
                    title="Video & Live"
                    description="Isi segmen pertama beranda. Saat live, siaran langsung menggantikan video."
                />

                {flash && (
                    <p
                        role="status"
                        className="rounded-xl border border-green-600/30 bg-green-600/10 px-4 py-3 text-sm"
                    >
                        {flash}
                    </p>
                )}

                <ModeCard mode={videoMode} />
                <LiveCard live={live} />
                <VideosCard videos={videos} rules={rules} />
            </div>
        </>
    );
}

function LiveCard({ live }: { live: Live }) {
    const form = useForm({
        url: live.url,
        title: live.title,
        is_live: live.isLive,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(liveUpdate.url(), { preserveScroll: true });
    };

    return (
        <Card>
            <CardHeader>
                <div className="flex flex-wrap items-center gap-2">
                    <CardTitle className="flex items-center gap-2">
                        <Radio className="size-5" />
                        Live streaming
                    </CardTitle>
                    {live.isOn ? (
                        <Badge variant="destructive">Sedang tampil</Badge>
                    ) : (
                        <Badge variant="outline">Tidak live</Badge>
                    )}
                </div>
                <CardDescription>
                    Tempel tautan YouTube (video live atau halaman live
                    channel). Tautan lain tampil sebagai tombol &ldquo;Tonton
                    live&rdquo;. Live mati sendiri setelah {live.autoOffHours}{' '}
                    jam bila lupa dimatikan.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form onSubmit={submit} className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="live-url">Tautan live streaming</Label>
                        <Input
                            id="live-url"
                            type="url"
                            inputMode="url"
                            placeholder="https://www.youtube.com/watch?v=…"
                            value={form.data.url}
                            onChange={(e) =>
                                form.setData('url', e.target.value)
                            }
                        />
                        <InputError message={form.errors.url} />
                        {live.url && !live.embeddable && (
                            <p className="text-sm text-muted-foreground">
                                Tautan ini tidak bisa disematkan, jadi tampil
                                sebagai tombol yang membuka tab baru.
                            </p>
                        )}
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="live-title">
                            Judul{' '}
                            <span className="font-normal text-muted-foreground">
                                (opsional)
                            </span>
                        </Label>
                        <Input
                            id="live-title"
                            placeholder="Ibadah Raya I"
                            value={form.data.title}
                            onChange={(e) =>
                                form.setData('title', e.target.value)
                            }
                        />
                        <InputError message={form.errors.title} />
                    </div>
                    <div className="flex items-center gap-3">
                        <Checkbox
                            id="live-on"
                            checked={form.data.is_live}
                            onCheckedChange={(checked) =>
                                form.setData('is_live', checked === true)
                            }
                        />
                        <Label htmlFor="live-on">
                            Sedang live, tampilkan di beranda
                        </Label>
                    </div>
                    {live.isOn && live.startedAt && (
                        <p className="text-sm text-muted-foreground">
                            Tampil sejak {live.startedAt}.
                        </p>
                    )}
                    <div>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? 'Menyimpan…' : 'Simpan'}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}

function VideosCard({
    videos,
    rules,
}: {
    videos: Video[];
    rules: Props['rules'];
}) {
    const fileInput = useRef<HTMLInputElement>(null);
    const [sizeError, setSizeError] = useState<string>();
    const form = useForm<{
        video: File | null;
        poster: File | null;
        title: string;
    }>({ video: null, poster: null, title: '' });
    const full = videos.length >= rules.maxCount;

    const pickVideo = (event: ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0] ?? null;

        // Catch oversized files before a long upload that would fail anyway.
        if (file && file.size > rules.maxMb * 1024 * 1024) {
            setSizeError(`Ukuran video maksimal ${rules.maxMb} MB.`);
            form.setData('video', null);
            event.target.value = '';

            return;
        }

        setSizeError(undefined);
        form.setData('video', file);
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(store.url(), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                form.reset();

                if (fileInput.current) {
                    fileInput.current.value = '';
                }
            },
        });
    };

    const move = (index: number, step: number) => {
        const ids = videos.map((video) => video.id);
        const target = index + step;
        [ids[index], ids[target]] = [ids[target], ids[index]];
        router.post(order.url(), { ids }, { preserveScroll: true });
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <Film className="size-5" />
                    Video beranda
                </CardTitle>
                <CardDescription>
                    Diputar tanpa suara, bergantian sesuai urutan. Bila belum
                    ada video, segmen menampilkan tema dari slide hero.
                </CardDescription>
            </CardHeader>
            <CardContent className="grid gap-6">
                <p className="flex items-start gap-2 rounded-xl bg-muted px-4 py-3 text-sm">
                    <Info className="mt-0.5 size-4 shrink-0" />
                    <span>
                        Saran: {rules.recommended}. Maksimal {rules.maxMb} MB
                        per video, {rules.maxCount} video.
                    </span>
                </p>

                <form
                    onSubmit={submit}
                    className="grid gap-4 rounded-xl border p-4"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="video-file">File video</Label>
                        <Input
                            ref={fileInput}
                            id="video-file"
                            type="file"
                            accept="video/mp4,video/webm"
                            disabled={full}
                            onChange={pickVideo}
                        />
                        <InputError message={sizeError ?? form.errors.video} />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="video-title">
                                Judul{' '}
                                <span className="font-normal text-muted-foreground">
                                    (opsional)
                                </span>
                            </Label>
                            <Input
                                id="video-title"
                                placeholder="Suasana Ibadah Raya"
                                value={form.data.title}
                                onChange={(e) =>
                                    form.setData('title', e.target.value)
                                }
                            />
                            <InputError message={form.errors.title} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="video-poster">
                                Gambar sampul{' '}
                                <span className="font-normal text-muted-foreground">
                                    (opsional)
                                </span>
                            </Label>
                            <Input
                                id="video-poster"
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                onChange={(e) =>
                                    form.setData(
                                        'poster',
                                        e.target.files?.[0] ?? null,
                                    )
                                }
                            />
                            <InputError message={form.errors.poster} />
                        </div>
                    </div>
                    {form.progress && (
                        <div
                            role="progressbar"
                            aria-label="Mengunggah video"
                            aria-valuenow={form.progress.percentage ?? 0}
                            aria-valuemin={0}
                            aria-valuemax={100}
                            className="h-2 overflow-hidden rounded-full bg-muted"
                        >
                            <div
                                className="h-full bg-primary transition-[width]"
                                style={{
                                    width: `${form.progress.percentage ?? 0}%`,
                                }}
                            />
                        </div>
                    )}
                    <div>
                        <Button
                            type="submit"
                            disabled={
                                full || !form.data.video || form.processing
                            }
                        >
                            <Upload />
                            {form.processing ? 'Mengunggah…' : 'Unggah video'}
                        </Button>
                        {full && (
                            <p className="mt-2 text-sm text-muted-foreground">
                                Sudah {rules.maxCount} video. Hapus salah satu
                                untuk menambah.
                            </p>
                        )}
                    </div>
                </form>

                {videos.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Belum ada video.
                    </p>
                ) : (
                    <ul className="grid gap-3">
                        {videos.map((video, index) => (
                            <li
                                key={video.id}
                                className="flex flex-col gap-4 rounded-xl border p-3 sm:flex-row sm:items-center"
                            >
                                <video
                                    src={video.url}
                                    poster={video.posterUrl ?? undefined}
                                    preload="metadata"
                                    muted
                                    playsInline
                                    controls
                                    className="aspect-video w-full rounded-lg bg-black object-cover sm:w-56"
                                />
                                <div className="min-w-0 flex-1">
                                    <p className="truncate font-medium">
                                        {video.title || `Video ${index + 1}`}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {video.sizeMb} MB ·{' '}
                                        {video.isActive
                                            ? 'Tampil'
                                            : 'Disembunyikan'}
                                    </p>
                                </div>
                                <div className="flex items-center gap-1">
                                    <Button
                                        type="button"
                                        size="icon"
                                        variant="ghost"
                                        disabled={index === 0}
                                        onClick={() => move(index, -1)}
                                        aria-label="Naikkan urutan"
                                    >
                                        <ArrowUp />
                                    </Button>
                                    <Button
                                        type="button"
                                        size="icon"
                                        variant="ghost"
                                        disabled={index === videos.length - 1}
                                        onClick={() => move(index, 1)}
                                        aria-label="Turunkan urutan"
                                    >
                                        <ArrowDown />
                                    </Button>
                                    <Button
                                        type="button"
                                        size="icon"
                                        variant="ghost"
                                        onClick={() =>
                                            router.patch(
                                                videoUpdate.url(video.id),
                                                {
                                                    title: video.title,
                                                    is_active: !video.isActive,
                                                },
                                                { preserveScroll: true },
                                            )
                                        }
                                        aria-label={
                                            video.isActive
                                                ? 'Sembunyikan video'
                                                : 'Tampilkan video'
                                        }
                                    >
                                        {video.isActive ? <Eye /> : <EyeOff />}
                                    </Button>
                                    <Button
                                        type="button"
                                        size="icon"
                                        variant="ghost"
                                        onClick={() => {
                                            if (
                                                window.confirm(
                                                    'Hapus video ini? File-nya ikut terhapus.',
                                                )
                                            ) {
                                                router.delete(
                                                    destroy.url(video.id),
                                                    { preserveScroll: true },
                                                );
                                            }
                                        }}
                                        aria-label="Hapus video"
                                    >
                                        <Trash2 />
                                    </Button>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </CardContent>
        </Card>
    );
}

const modes = [
    {
        value: 'frame',
        label: 'Bingkai',
        description:
            'Video diputar dalam bingkai lebar di bawah tulisan sambutan.',
    },
    {
        value: 'background',
        label: 'Latar',
        description:
            'Video memenuhi bagian atas beranda di belakang sambutan, dengan blur tipis.',
    },
] as const;

/** Frame or background: how the homepage videos are shown. */
function ModeCard({ mode }: { mode: Props['videoMode'] }) {
    const form = useForm({ video_mode: mode });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(modeUpdate.url(), { preserveScroll: true });
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>Tampilan video</CardTitle>
                <CardDescription>
                    Saat live, siaran selalu tampil di bingkai agar tidak
                    terhalang tulisan.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form onSubmit={submit} className="grid gap-4">
                    <fieldset className="grid gap-3 sm:grid-cols-2">
                        <legend className="sr-only">Tampilan video</legend>
                        {modes.map((option) => (
                            <label
                                key={option.value}
                                className="flex cursor-pointer gap-3 rounded-xl border p-4 has-[:checked]:border-primary has-[:checked]:bg-primary/5"
                            >
                                <input
                                    type="radio"
                                    name="video_mode"
                                    value={option.value}
                                    checked={
                                        form.data.video_mode === option.value
                                    }
                                    onChange={() =>
                                        form.setData('video_mode', option.value)
                                    }
                                    className="mt-1 accent-[var(--primary)]"
                                />
                                <span>
                                    <span className="block font-medium">
                                        {option.label}
                                    </span>
                                    <span className="block text-sm text-muted-foreground">
                                        {option.description}
                                    </span>
                                </span>
                            </label>
                        ))}
                    </fieldset>
                    <InputError message={form.errors.video_mode} />
                    <div>
                        <Button
                            type="submit"
                            disabled={form.processing || !form.isDirty}
                        >
                            {form.processing ? 'Menyimpan…' : 'Simpan'}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}

LandingMedia.layout = {
    breadcrumbs: [{ title: 'Landing Page', href: '/admin/landing' }],
};
