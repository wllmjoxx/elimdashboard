import { Head, useForm } from '@inertiajs/react';
import { ImageIcon, Info } from 'lucide-react';
import { useEffect, useState } from 'react';
import type { ChangeEvent, FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
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
import { update } from '@/routes/landing/segments';

type Align = 'left' | 'center' | 'right';

type Segment = {
    key: string;
    label: string;
    eyebrow: string;
    title: string;
    text: string;
    button_label: string;
    button_href: string;
    align: Align;
    is_active: boolean;
    image_url: string | null;
};

type ImageRules = {
    formats: string;
    accept: string;
    maxMb: number;
    minWidth: number;
    minHeight: number;
    maxWidth: number;
    maxHeight: number;
    minRatio: string;
    recommended: string;
};

type Props = {
    segments: Segment[];
    imageRules: ImageRules;
};

const fieldClass =
    'w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

function UploadRules({ rules }: { rules: ImageRules }) {
    return (
        <div className="flex gap-3 rounded-xl border bg-muted/40 p-4 text-sm">
            <Info className="mt-0.5 size-4 shrink-0 text-sky" />
            <div className="space-y-1">
                <p className="font-medium">Ketentuan gambar latar segmen</p>
                <ul className="list-disc space-y-0.5 pl-4 text-muted-foreground">
                    <li>
                        Format {rules.formats}; WebP disarankan agar halaman
                        cepat.
                    </li>
                    <li>Ukuran file maksimal {rules.maxMb} MB.</li>
                    <li>
                        Resolusi minimal {rules.minWidth} × {rules.minHeight}{' '}
                        px, maksimal {rules.maxWidth} × {rules.maxHeight} px.
                    </li>
                    <li>
                        Lanskap dengan rasio minimal {rules.minRatio}; ideal{' '}
                        {rules.recommended}.
                    </li>
                    <li>
                        Gambar dipotong mengikuti lebar layar, jadi letakkan
                        objek penting di tengah. Teks tampil putih di atas
                        lapisan gelap.
                    </li>
                </ul>
            </div>
        </div>
    );
}

/** Reads the pixel size of a picked file so problems show before upload. */
function useImageSize(file: File | null) {
    const [size, setSize] = useState<{ width: number; height: number } | null>(
        null,
    );
    const [preview, setPreview] = useState<string | null>(null);

    useEffect(() => {
        if (!file) {
            setSize(null);
            setPreview(null);

            return;
        }

        const url = URL.createObjectURL(file);
        const image = new Image();
        image.onload = () =>
            setSize({ width: image.naturalWidth, height: image.naturalHeight });
        image.src = url;
        setPreview(url);

        return () => URL.revokeObjectURL(url);
    }, [file]);

    return { size, preview };
}

function SegmentForm({
    segment,
    rules,
}: {
    segment: Segment;
    rules: ImageRules;
}) {
    const form = useForm<{
        eyebrow: string;
        title: string;
        text: string;
        button_label: string;
        button_href: string;
        align: Align;
        is_active: boolean;
        image: File | null;
        remove_image: boolean;
    }>({
        eyebrow: segment.eyebrow,
        title: segment.title,
        text: segment.text,
        button_label: segment.button_label,
        button_href: segment.button_href,
        align: segment.align,
        is_active: segment.is_active,
        image: null,
        remove_image: false,
    });

    const { size, preview } = useImageSize(form.data.image);
    const tooSmall =
        size !== null &&
        (size.width < rules.minWidth || size.height < rules.minHeight);
    const tooLarge = form.data.image
        ? form.data.image.size > rules.maxMb * 1024 * 1024
        : false;
    const shownImage =
        preview ?? (form.data.remove_image ? null : segment.image_url);
    const id = (field: string) => `${segment.key}-${field}`;

    const pickImage = (event: ChangeEvent<HTMLInputElement>) => {
        form.setData((data) => ({
            ...data,
            image: event.target.files?.[0] ?? null,
            remove_image: false,
        }));
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(update.url(segment.key), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () =>
                form.setData((data) => ({
                    ...data,
                    image: null,
                    remove_image: false,
                })),
        });
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>{segment.label}</CardTitle>
                <CardDescription>
                    Banner lebar di halaman Home dengan gambar latar, judul,
                    teks, dan tombol.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form onSubmit={submit} className="grid gap-6 lg:grid-cols-2">
                    <div className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor={id('eyebrow')}>Label kecil</Label>
                            <Input
                                id={id('eyebrow')}
                                value={form.data.eyebrow}
                                onChange={(e) =>
                                    form.setData('eyebrow', e.target.value)
                                }
                                maxLength={60}
                                placeholder="Contoh: Menara Doa"
                            />
                            <InputError message={form.errors.eyebrow} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor={id('title')}>Judul</Label>
                            <Input
                                id={id('title')}
                                value={form.data.title}
                                onChange={(e) =>
                                    form.setData('title', e.target.value)
                                }
                                maxLength={120}
                                required
                            />
                            <InputError message={form.errors.title} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor={id('text')}>Teks</Label>
                            <textarea
                                id={id('text')}
                                value={form.data.text}
                                onChange={(e) =>
                                    form.setData('text', e.target.value)
                                }
                                maxLength={600}
                                rows={4}
                                className={fieldClass}
                            />
                            <InputError message={form.errors.text} />
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor={id('button_label')}>
                                    Label tombol
                                </Label>
                                <Input
                                    id={id('button_label')}
                                    value={form.data.button_label}
                                    onChange={(e) =>
                                        form.setData(
                                            'button_label',
                                            e.target.value,
                                        )
                                    }
                                    maxLength={40}
                                    placeholder="Contoh: Lihat jadwal"
                                />
                                <InputError
                                    message={form.errors.button_label}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor={id('button_href')}>
                                    Tautan tombol
                                </Label>
                                <Input
                                    id={id('button_href')}
                                    value={form.data.button_href}
                                    onChange={(e) =>
                                        form.setData(
                                            'button_href',
                                            e.target.value,
                                        )
                                    }
                                    maxLength={255}
                                    placeholder="/jadwal atau https://…"
                                />
                                <InputError message={form.errors.button_href} />
                            </div>
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor={id('align')}>Posisi teks</Label>
                                <select
                                    id={id('align')}
                                    value={form.data.align}
                                    onChange={(e) =>
                                        form.setData(
                                            'align',
                                            e.target.value as Align,
                                        )
                                    }
                                    className={fieldClass}
                                >
                                    <option value="left">Kiri</option>
                                    <option value="center">Tengah</option>
                                    <option value="right">Kanan</option>
                                </select>
                                <InputError message={form.errors.align} />
                            </div>
                            <div className="flex items-center gap-2 sm:pt-6">
                                <Checkbox
                                    id={id('is_active')}
                                    checked={form.data.is_active}
                                    onCheckedChange={(checked) =>
                                        form.setData(
                                            'is_active',
                                            checked === true,
                                        )
                                    }
                                />
                                <Label htmlFor={id('is_active')}>
                                    Tampilkan di Home
                                </Label>
                            </div>
                        </div>
                    </div>

                    <div className="space-y-4">
                        <div className="relative aspect-video overflow-hidden rounded-xl border bg-muted">
                            {shownImage ? (
                                <img
                                    src={shownImage}
                                    alt={`Pratinjau ${segment.label}`}
                                    className="size-full object-cover"
                                />
                            ) : (
                                <div className="grid size-full place-items-center text-sm text-muted-foreground">
                                    <span className="flex flex-col items-center gap-2">
                                        <ImageIcon className="size-8" />
                                        Belum ada gambar (memakai gradasi warna)
                                    </span>
                                </div>
                            )}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor={id('image')}>Gambar latar</Label>
                            <Input
                                id={id('image')}
                                type="file"
                                accept={rules.accept}
                                onChange={pickImage}
                            />
                            {size && (
                                <p
                                    className={
                                        tooSmall
                                            ? 'text-sm text-destructive'
                                            : 'text-sm text-muted-foreground'
                                    }
                                >
                                    Resolusi {size.width} × {size.height} px
                                    {tooSmall &&
                                        ` — kurang dari minimal ${rules.minWidth} × ${rules.minHeight} px`}
                                </p>
                            )}
                            {tooLarge && (
                                <p className="text-sm text-destructive">
                                    File lebih dari {rules.maxMb} MB.
                                </p>
                            )}
                            <InputError message={form.errors.image} />
                        </div>
                        {segment.image_url && !form.data.image && (
                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id={id('remove_image')}
                                    checked={form.data.remove_image}
                                    onCheckedChange={(checked) =>
                                        form.setData(
                                            'remove_image',
                                            checked === true,
                                        )
                                    }
                                />
                                <Label htmlFor={id('remove_image')}>
                                    Hapus gambar saat ini
                                </Label>
                            </div>
                        )}
                    </div>

                    <div className="flex items-center gap-3 lg:col-span-2">
                        <Button
                            type="submit"
                            disabled={form.processing || tooSmall || tooLarge}
                        >
                            {form.processing ? 'Menyimpan…' : 'Simpan'}
                        </Button>
                        {form.recentlySuccessful && (
                            <p className="text-sm text-muted-foreground">
                                Tersimpan.
                            </p>
                        )}
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}

export default function LandingIndex({ segments, imageRules }: Props) {
    return (
        <>
            <Head title="Landing Page" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Segmen Home"
                    description="Atur tiga banner bergambar yang tampil di halaman Home."
                />
                <UploadRules rules={imageRules} />
                {segments.map((segment) => (
                    <SegmentForm
                        key={segment.key}
                        segment={segment}
                        rules={imageRules}
                    />
                ))}
            </div>
        </>
    );
}

LandingIndex.layout = {
    breadcrumbs: [{ title: 'Landing Page', href: '/admin/landing' }],
};
