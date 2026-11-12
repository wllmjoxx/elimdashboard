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
import { index, store, update } from '@/routes/announcements';

type EditableAnnouncement = {
    id: number;
    title: string;
    body: string;
    isPinned: boolean;
    isPublished: boolean;
    publishedOn: string;
    expiresOn: string;
    url: string;
};

type Props = {
    announcement: EditableAnnouncement | null;
    today: string;
};

/** Admin › Pengumuman › new or edit. */
export default function AnnouncementForm({ announcement, today }: Props) {
    const form = useForm({
        title: announcement?.title ?? '',
        body: announcement?.body ?? '',
        is_pinned: announcement?.isPinned ?? false,
        is_published: announcement?.isPublished ?? true,
        published_on: announcement?.publishedOn ?? today,
        expires_on: announcement?.expiresOn ?? '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();

        if (announcement) {
            form.put(update.url(announcement.id));
        } else {
            form.post(store.url());
        }
    };

    const title = announcement ? 'Ubah pengumuman' : 'Pengumuman baru';

    return (
        <>
            <Head title={title} />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div>
                    <Link
                        href={index()}
                        className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" />
                        Semua pengumuman
                    </Link>
                    <Heading
                        title={title}
                        description="Setiap baris kosong di isi menjadi paragraf baru."
                    />
                </div>

                <form onSubmit={submit} className="grid max-w-3xl gap-6">
                    <Card>
                        <CardContent className="grid gap-4 pt-6">
                            <div className="grid gap-2">
                                <Label htmlFor="title">Judul</Label>
                                <Input
                                    id="title"
                                    value={form.data.title}
                                    onChange={(e) =>
                                        form.setData('title', e.target.value)
                                    }
                                />
                                <InputError message={form.errors.title} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="body">Isi</Label>
                                <textarea
                                    id="body"
                                    rows={8}
                                    value={form.data.body}
                                    onChange={(e) =>
                                        form.setData('body', e.target.value)
                                    }
                                    className="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                />
                                <InputError message={form.errors.body} />
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="published_on">
                                        Tayang mulai
                                    </Label>
                                    <Input
                                        id="published_on"
                                        type="date"
                                        value={form.data.published_on}
                                        onChange={(e) =>
                                            form.setData(
                                                'published_on',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.published_on}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="expires_on">
                                        Berakhir{' '}
                                        <span className="font-normal text-muted-foreground">
                                            (opsional)
                                        </span>
                                    </Label>
                                    <Input
                                        id="expires_on"
                                        type="date"
                                        value={form.data.expires_on}
                                        onChange={(e) =>
                                            form.setData(
                                                'expires_on',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.expires_on}
                                    />
                                </div>
                            </div>
                            <div className="flex items-center gap-3">
                                <Checkbox
                                    id="is_pinned"
                                    checked={form.data.is_pinned}
                                    onCheckedChange={(checked) =>
                                        form.setData(
                                            'is_pinned',
                                            checked === true,
                                        )
                                    }
                                />
                                <Label htmlFor="is_pinned">
                                    Sematkan di paling atas
                                </Label>
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
                                    Tampilkan di situs (kosongkan untuk draf)
                                </Label>
                            </div>
                        </CardContent>
                    </Card>

                    <div>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? 'Menyimpan…' : 'Simpan'}
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

AnnouncementForm.layout = {
    breadcrumbs: [{ title: 'Pengumuman', href: '/admin/announcements' }],
};
