import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import type { FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index, store, update } from '@/routes/events';

type EditableEvent = {
    id: number;
    title: string;
    summary: string;
    body: string;
    location: string;
    startsAt: string;
    endsAt: string;
    registrationUrl: string;
    isPublished: boolean;
    imageUrl: string | null;
    url: string;
};

type Props = { event: EditableEvent | null };

const textareaClass =
    'w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

function Optional() {
    return (
        <span className="font-normal text-muted-foreground"> (opsional)</span>
    );
}

/** Admin › Kegiatan › new or edit. */
export default function EventForm({ event }: Props) {
    const form = useForm<{
        title: string;
        summary: string;
        body: string;
        location: string;
        starts_at: string;
        ends_at: string;
        registration_url: string;
        is_published: boolean;
        image: File | null;
        remove_image: boolean;
    }>({
        title: event?.title ?? '',
        summary: event?.summary ?? '',
        body: event?.body ?? '',
        location: event?.location ?? '',
        starts_at: event?.startsAt ?? '',
        ends_at: event?.endsAt ?? '',
        registration_url: event?.registrationUrl ?? '',
        is_published: event?.isPublished ?? true,
        image: null,
        remove_image: false,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();

        if (event) {
            // Multipart cannot be sent with PUT; Laravel reads _method.
            form.transform((data) => ({ ...data, _method: 'put' }));
            form.post(update.url(event.id), { forceFormData: true });
        } else {
            form.post(store.url(), { forceFormData: true });
        }
    };

    const field = (
        name: 'title' | 'location' | 'registration_url',
        label: string,
        optional = false,
        placeholder?: string,
    ) => (
        <div className="grid gap-2">
            <Label htmlFor={name}>
                {label}
                {optional && <Optional />}
            </Label>
            <Input
                id={name}
                value={form.data[name]}
                placeholder={placeholder}
                onChange={(e) => form.setData(name, e.target.value)}
            />
            <InputError message={form.errors[name]} />
        </div>
    );

    return (
        <>
            <Head title={event ? 'Ubah kegiatan' : 'Kegiatan baru'} />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div>
                    <Link
                        href={index()}
                        className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" />
                        Semua kegiatan
                    </Link>
                    <Heading
                        title={event ? 'Ubah kegiatan' : 'Kegiatan baru'}
                        description="Waktu dalam WIB. Tanpa waktu selesai, kegiatan dianggap berlangsung sampai akhir hari."
                    />
                </div>

                <form onSubmit={submit} className="grid max-w-3xl gap-6">
                    <Card>
                        <CardContent className="grid gap-4 pt-6">
                            {field('title', 'Judul')}
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="starts_at">Mulai</Label>
                                    <Input
                                        id="starts_at"
                                        type="datetime-local"
                                        value={form.data.starts_at}
                                        onChange={(e) =>
                                            form.setData(
                                                'starts_at',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.starts_at}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="ends_at">
                                        Selesai
                                        <Optional />
                                    </Label>
                                    <Input
                                        id="ends_at"
                                        type="datetime-local"
                                        value={form.data.ends_at}
                                        onChange={(e) =>
                                            form.setData(
                                                'ends_at',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError message={form.errors.ends_at} />
                                </div>
                            </div>
                            {field(
                                'location',
                                'Lokasi',
                                true,
                                'Gedung GGP Elim Ciputat',
                            )}
                            <div className="grid gap-2">
                                <Label htmlFor="summary">
                                    Ringkasan
                                    <Optional />
                                </Label>
                                <textarea
                                    id="summary"
                                    rows={2}
                                    maxLength={300}
                                    value={form.data.summary}
                                    onChange={(e) =>
                                        form.setData('summary', e.target.value)
                                    }
                                    className={textareaClass}
                                />
                                <InputError message={form.errors.summary} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="body">
                                    Deskripsi lengkap
                                    <Optional />
                                </Label>
                                <textarea
                                    id="body"
                                    rows={6}
                                    value={form.data.body}
                                    onChange={(e) =>
                                        form.setData('body', e.target.value)
                                    }
                                    className={textareaClass}
                                />
                                <InputError message={form.errors.body} />
                            </div>
                            {field(
                                'registration_url',
                                'Tautan pendaftaran',
                                true,
                                'https://forms.gle/…',
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="grid gap-4 pt-6">
                            <div className="grid gap-2">
                                <Label htmlFor="image">
                                    Gambar atau poster
                                    <Optional />
                                </Label>
                                {event?.imageUrl && !form.data.remove_image && (
                                    <img
                                        src={event.imageUrl}
                                        alt=""
                                        className="aspect-video w-full max-w-sm rounded-xl border object-cover"
                                    />
                                )}
                                <Input
                                    id="image"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    onChange={(e) =>
                                        form.setData(
                                            'image',
                                            e.target.files?.[0] ?? null,
                                        )
                                    }
                                />
                                <InputError message={form.errors.image} />
                                {event?.imageUrl && (
                                    <div className="flex items-center gap-2">
                                        <Checkbox
                                            id="remove_image"
                                            checked={form.data.remove_image}
                                            onCheckedChange={(checked) =>
                                                form.setData(
                                                    'remove_image',
                                                    checked === true,
                                                )
                                            }
                                        />
                                        <Label htmlFor="remove_image">
                                            Hapus gambar
                                        </Label>
                                    </div>
                                )}
                            </div>
                            <div className="flex items-center gap-3">
                                <Checkbox
                                    id="is_published"
                                    checked={form.data.is_published}
                                    onCheckedChange={(checked) =>
                                        form.setData(
                                            'is_published',
                                            checked === true,
                                        )
                                    }
                                />
                                <Label htmlFor="is_published">
                                    Tampilkan di situs
                                </Label>
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex flex-wrap items-center gap-3">
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? 'Menyimpan…' : 'Simpan'}
                        </Button>
                        {event && (
                            <a
                                href={event.url}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="text-sm text-muted-foreground underline-offset-4 hover:underline"
                            >
                                Lihat di situs
                            </a>
                        )}
                    </div>
                </form>
            </div>
        </>
    );
}

EventForm.layout = {
    breadcrumbs: [{ title: 'Kegiatan', href: '/admin/events' }],
};
