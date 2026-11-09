import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { DevotionalSummary } from '@/modules/devotionals/types';
import { index, store, update } from '@/routes/devotionals';

type Props = {
    devotional:
        | (DevotionalSummary & { scriptureText: string | null; body: string })
        | null;
    defaultDate: string;
    canPublish: boolean;
};

const textareaClass =
    'w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

/** Write or edit a devotional (PRD 4.8). */
export default function DevotionalForm({
    devotional,
    defaultDate,
    canPublish,
}: Props) {
    const form = useForm({
        title: devotional?.title ?? '',
        scripture_ref: devotional?.scriptureRef ?? '',
        scripture_text: devotional?.scriptureText ?? '',
        body: devotional?.body ?? '',
        publish_date: devotional?.publishDate ?? defaultDate,
        publish: false,
    });

    const isPublished = devotional?.status === 'published';

    const submit = (publishNow: boolean) => (event?: FormEvent) => {
        event?.preventDefault();
        form.transform((data) => ({ ...data, publish: publishNow }));

        if (devotional) {
            form.put(update.url(devotional.id));
        } else {
            form.post(store.url());
        }
    };

    return (
        <>
            <Head title={devotional ? 'Ubah renungan' : 'Tulis renungan'} />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <Heading
                    title={devotional ? 'Ubah renungan' : 'Tulis renungan'}
                    description={
                        canPublish
                            ? 'Simpan sebagai draf atau terbitkan pada tanggal terbit.'
                            : 'Renungan Anda tersimpan sebagai draf dan terbit setelah ditinjau Admin.'
                    }
                />

                <Card>
                    <CardContent>
                        <form onSubmit={submit(false)} className="grid gap-5">
                            <div className="grid gap-2">
                                <Label htmlFor="title">Judul</Label>
                                <Input
                                    id="title"
                                    value={form.data.title}
                                    onChange={(e) =>
                                        form.setData('title', e.target.value)
                                    }
                                    maxLength={150}
                                    required
                                />
                                <InputError message={form.errors.title} />
                            </div>

                            <div className="grid gap-5 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="scripture_ref">
                                        Ayat rujukan
                                    </Label>
                                    <Input
                                        id="scripture_ref"
                                        value={form.data.scripture_ref}
                                        onChange={(e) =>
                                            form.setData(
                                                'scripture_ref',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Contoh: Mazmur 23:1"
                                        maxLength={100}
                                        required
                                    />
                                    <InputError
                                        message={form.errors.scripture_ref}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="publish_date">
                                        Tanggal terbit
                                    </Label>
                                    <Input
                                        id="publish_date"
                                        type="date"
                                        value={form.data.publish_date}
                                        onChange={(e) =>
                                            form.setData(
                                                'publish_date',
                                                e.target.value,
                                            )
                                        }
                                        required
                                    />
                                    <p className="text-xs text-muted-foreground">
                                        Tanggal di masa depan membuat renungan
                                        terjadwal.
                                    </p>
                                    <InputError
                                        message={form.errors.publish_date}
                                    />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="scripture_text">
                                    Isi ayat{' '}
                                    <span className="font-normal text-muted-foreground">
                                        (opsional)
                                    </span>
                                </Label>
                                <textarea
                                    id="scripture_text"
                                    value={form.data.scripture_text}
                                    onChange={(e) =>
                                        form.setData(
                                            'scripture_text',
                                            e.target.value,
                                        )
                                    }
                                    rows={2}
                                    maxLength={1000}
                                    className={textareaClass}
                                />
                                <InputError
                                    message={form.errors.scripture_text}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="body">Isi renungan</Label>
                                <textarea
                                    id="body"
                                    value={form.data.body}
                                    onChange={(e) =>
                                        form.setData('body', e.target.value)
                                    }
                                    rows={12}
                                    maxLength={20000}
                                    required
                                    className={textareaClass}
                                />
                                <p className="text-xs text-muted-foreground">
                                    Pisahkan paragraf dengan satu baris kosong.
                                </p>
                                <InputError message={form.errors.body} />
                            </div>

                            <div className="flex flex-wrap items-center gap-3">
                                {canPublish && !isPublished && (
                                    <Button
                                        type="button"
                                        onClick={submit(true)}
                                        disabled={form.processing}
                                    >
                                        Simpan dan terbitkan
                                    </Button>
                                )}
                                <Button
                                    type="submit"
                                    variant={
                                        canPublish && !isPublished
                                            ? 'outline'
                                            : 'default'
                                    }
                                    disabled={form.processing}
                                >
                                    {isPublished
                                        ? 'Simpan perubahan'
                                        : 'Simpan draf'}
                                </Button>
                                <Button asChild variant="ghost">
                                    <Link href={index()}>Batal</Link>
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

DevotionalForm.layout = {
    breadcrumbs: [{ title: 'Renungan', href: '/admin/devotionals' }],
};
