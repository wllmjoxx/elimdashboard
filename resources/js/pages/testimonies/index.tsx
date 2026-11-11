import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    Check,
    Clock,
    Pencil,
    PencilLine,
    Star,
    Trash2,
    X,
} from 'lucide-react';
import { useState } from 'react';
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
import { cn } from '@/lib/utils';
import {
    destroy,
    feature,
    index,
    moderate,
    update,
} from '@/routes/testimonies';

type Status = 'pending' | 'revision' | 'approved' | 'rejected';

type Row = {
    id: number;
    title: string;
    body: string;
    category: string;
    categoryLabel: string;
    displayName: string | null;
    status: Status;
    isFeatured: boolean;
    note: string | null;
    moderator: string | null;
    receivedAt: string;
    overdue: boolean;
};

type Props = {
    status: Status;
    statuses: { value: Status; label: string; count: number }[];
    categories: { value: string; label: string }[];
    testimonies: {
        data: Row[];
        links: { url: string | null; label: string; active: boolean }[];
        last_page: number;
    };
    moderationHours: number;
    flash: string | null;
};

const fieldClass =
    'w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

const received = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
});

function EditDialog({
    row,
    categories,
    onClose,
}: {
    row: Row | null;
    categories: Props['categories'];
    onClose: () => void;
}) {
    const form = useForm({
        title: row?.title ?? '',
        body: row?.body ?? '',
        category: row?.category ?? '',
        display_name: row?.displayName ?? '',
    });

    if (!row) {
        return null;
    }

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Ubah kesaksian</DialogTitle>
                    <DialogDescription>
                        Rapikan kata-kata atau hapus detail yang bisa mengungkap
                        identitas seseorang.
                    </DialogDescription>
                </DialogHeader>
                <div className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="edit-title">Judul</Label>
                        <Input
                            id="edit-title"
                            value={form.data.title}
                            onChange={(e) =>
                                form.setData('title', e.target.value)
                            }
                        />
                        <InputError message={form.errors.title} />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="edit-category">Kategori</Label>
                            <select
                                id="edit-category"
                                className={fieldClass}
                                value={form.data.category}
                                onChange={(e) =>
                                    form.setData('category', e.target.value)
                                }
                            >
                                {categories.map((category) => (
                                    <option
                                        key={category.value}
                                        value={category.value}
                                    >
                                        {category.label}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="edit-name">Nama tampilan</Label>
                            <Input
                                id="edit-name"
                                value={form.data.display_name}
                                onChange={(e) =>
                                    form.setData('display_name', e.target.value)
                                }
                            />
                        </div>
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="edit-body">Kesaksian</Label>
                        <textarea
                            id="edit-body"
                            rows={8}
                            className={fieldClass}
                            value={form.data.body}
                            onChange={(e) =>
                                form.setData('body', e.target.value)
                            }
                        />
                        <InputError message={form.errors.body} />
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" onClick={onClose}>
                        Batal
                    </Button>
                    <Button
                        disabled={form.processing}
                        onClick={() =>
                            form.put(update.url(row.id), {
                                preserveScroll: true,
                                onSuccess: onClose,
                            })
                        }
                    >
                        Simpan
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function NoteDialog({
    action,
    onClose,
}: {
    action: { row: Row; status: 'rejected' | 'revision' } | null;
    onClose: () => void;
}) {
    const [note, setNote] = useState('');
    const [error, setError] = useState<string>();

    if (!action) {
        return null;
    }

    const reject = action.status === 'rejected';

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {reject ? 'Tolak kesaksian' : 'Tandai perlu revisi'}
                    </DialogTitle>
                    <DialogDescription>
                        Catatan ini hanya terlihat oleh Admin.
                    </DialogDescription>
                </DialogHeader>
                <div className="grid gap-2">
                    <Label htmlFor="note">Catatan internal</Label>
                    <textarea
                        id="note"
                        rows={4}
                        className={fieldClass}
                        value={note}
                        onChange={(e) => setNote(e.target.value)}
                    />
                    <InputError message={error} />
                </div>
                <DialogFooter>
                    <Button variant="outline" onClick={onClose}>
                        Batal
                    </Button>
                    <Button
                        variant={reject ? 'destructive' : 'default'}
                        disabled={note.trim() === ''}
                        onClick={() =>
                            router.post(
                                moderate.url(action.row.id),
                                { status: action.status, note },
                                {
                                    preserveScroll: true,
                                    onError: (errors) => setError(errors.note),
                                    onSuccess: onClose,
                                },
                            )
                        }
                    >
                        {reject ? 'Tolak' : 'Simpan'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

/** Moderation of anonymous testimonies (PRD 4.7). */
export default function TestimoniesIndex({
    status,
    statuses,
    categories,
    testimonies,
    moderationHours,
    flash,
}: Props) {
    const [editing, setEditing] = useState<Row | null>(null);
    const [noting, setNoting] = useState<{
        row: Row;
        status: 'rejected' | 'revision';
    } | null>(null);

    const post = (url: string, data: Record<string, string | boolean>) =>
        router.post(url, data, { preserveScroll: true });

    return (
        <>
            <Head title="Kesaksian" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Kesaksian"
                    description={`Tinjau kesaksian anonim. Target moderasi ${moderationHours} jam. Pengirim tidak dapat diketahui.`}
                />

                {flash && (
                    <p
                        role="status"
                        className="rounded-xl border border-green-600/30 bg-green-600/10 px-4 py-3 text-sm"
                    >
                        {flash}
                    </p>
                )}

                <nav
                    aria-label="Status kesaksian"
                    className="flex flex-wrap gap-2"
                >
                    {statuses.map((item) => (
                        <Link
                            key={item.value}
                            href={index.url({ query: { status: item.value } })}
                            preserveScroll
                            aria-current={
                                item.value === status ? 'page' : undefined
                            }
                            className={cn(
                                'inline-flex items-center gap-2 rounded-full border px-4 py-1.5 text-sm font-medium transition-colors',
                                item.value === status
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : 'hover:bg-muted',
                            )}
                        >
                            {item.label}
                            <span className="tabular-nums opacity-80">
                                {item.count}
                            </span>
                        </Link>
                    ))}
                </nav>

                {testimonies.data.length === 0 ? (
                    <p className="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">
                        Tidak ada kesaksian dengan status ini.
                    </p>
                ) : (
                    <ul className="grid gap-4">
                        {testimonies.data.map((row) => (
                            <li key={row.id} className="rounded-xl border p-5">
                                <div className="flex flex-wrap items-start justify-between gap-2">
                                    <div>
                                        <h2 className="font-semibold">
                                            {row.title}
                                        </h2>
                                        <p className="text-sm text-muted-foreground">
                                            {row.displayName || 'Anonim'} ·{' '}
                                            {row.categoryLabel} · diterima{' '}
                                            {received.format(
                                                new Date(row.receivedAt),
                                            )}
                                        </p>
                                    </div>
                                    <div className="flex flex-wrap gap-1">
                                        {row.overdue && (
                                            <Badge variant="destructive">
                                                <Clock />
                                                Lewat {moderationHours} jam
                                            </Badge>
                                        )}
                                        {row.isFeatured && (
                                            <Badge>
                                                <Star />
                                                Tampil di Home
                                            </Badge>
                                        )}
                                    </div>
                                </div>
                                <p className="mt-3 whitespace-pre-line">
                                    {row.body}
                                </p>
                                {row.note && (
                                    <p className="mt-3 rounded-lg bg-muted px-3 py-2 text-sm">
                                        Catatan internal: {row.note}
                                        {row.moderator && (
                                            <span className="text-muted-foreground">
                                                {' '}
                                                ({row.moderator})
                                            </span>
                                        )}
                                    </p>
                                )}
                                <div className="mt-4 flex flex-wrap gap-2 border-t pt-4">
                                    {row.status !== 'approved' && (
                                        <Button
                                            size="sm"
                                            onClick={() =>
                                                post(moderate.url(row.id), {
                                                    status: 'approved',
                                                })
                                            }
                                        >
                                            <Check />
                                            Setujui
                                        </Button>
                                    )}
                                    {row.status === 'approved' && (
                                        <Button
                                            size="sm"
                                            variant={
                                                row.isFeatured
                                                    ? 'outline'
                                                    : 'default'
                                            }
                                            onClick={() =>
                                                post(feature.url(row.id), {
                                                    featured: !row.isFeatured,
                                                })
                                            }
                                        >
                                            <Star />
                                            {row.isFeatured
                                                ? 'Lepas dari Home'
                                                : 'Tampilkan di Home'}
                                        </Button>
                                    )}
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() => setEditing(row)}
                                    >
                                        <Pencil />
                                        Ubah
                                    </Button>
                                    {row.status !== 'revision' && (
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            onClick={() =>
                                                setNoting({
                                                    row,
                                                    status: 'revision',
                                                })
                                            }
                                        >
                                            <PencilLine />
                                            Perlu revisi
                                        </Button>
                                    )}
                                    {row.status !== 'rejected' && (
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            onClick={() =>
                                                setNoting({
                                                    row,
                                                    status: 'rejected',
                                                })
                                            }
                                        >
                                            <X />
                                            Tolak
                                        </Button>
                                    )}
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        className="ml-auto"
                                        aria-label={`Hapus ${row.title}`}
                                        onClick={() => {
                                            if (
                                                window.confirm(
                                                    `Hapus kesaksian "${row.title}"?`,
                                                )
                                            ) {
                                                router.delete(
                                                    destroy.url(row.id),
                                                    { preserveScroll: true },
                                                );
                                            }
                                        }}
                                    >
                                        <Trash2 />
                                    </Button>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}

                {testimonies.last_page > 1 && (
                    <nav aria-label="Halaman" className="flex flex-wrap gap-1">
                        {testimonies.links.map((link) =>
                            link.url ? (
                                <Link
                                    key={link.label}
                                    href={link.url}
                                    preserveScroll
                                    className={cn(
                                        'rounded-md border px-3 py-1 text-sm',
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

            <EditDialog
                key={editing ? `edit-${editing.id}` : 'edit-none'}
                row={editing}
                categories={categories}
                onClose={() => setEditing(null)}
            />
            <NoteDialog
                key={
                    noting
                        ? `note-${noting.row.id}-${noting.status}`
                        : 'note-none'
                }
                action={noting}
                onClose={() => setNoting(null)}
            />
        </>
    );
}

TestimoniesIndex.layout = {
    breadcrumbs: [{ title: 'Kesaksian', href: '/admin/testimonies' }],
};
