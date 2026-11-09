import { Head, Link, router } from '@inertiajs/react';
import { Eye, Pencil, Plus, Send, Trash2, Undo2 } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { DevotionalSummary, Paginated } from '@/modules/devotionals/types';
import { formatDate } from '@/modules/devotionals/types';
import {
    create,
    destroy,
    edit,
    index,
    publish,
    unpublish,
} from '@/routes/devotionals';
import { show as publicShow } from '@/routes/devotionals/public';

type Row = DevotionalSummary & {
    can: { update: boolean; publish: boolean; delete: boolean };
};

type Props = {
    tab: 'draft' | 'scheduled' | 'published';
    tabs: { value: string; label: string; count: number }[];
    devotionals: Paginated<Row>;
    canCreate: boolean;
    flash: string | null;
};

const emptyText: Record<Props['tab'], string> = {
    draft: 'Tidak ada draf yang menunggu tinjauan.',
    scheduled: 'Belum ada renungan yang dijadwalkan.',
    published: 'Belum ada renungan yang terbit.',
};

/** Admin list of devotionals (PRD 4.8). */
export default function DevotionalsIndex({
    tab,
    tabs,
    devotionals,
    canCreate,
    flash,
}: Props) {
    const post = (url: string) =>
        router.post(url, {}, { preserveScroll: true });

    const remove = (row: Row) => {
        if (window.confirm(`Hapus renungan "${row.title}"?`)) {
            router.delete(destroy.url(row.id), { preserveScroll: true });
        }
    };

    return (
        <>
            <Head title="Renungan" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Renungan Harian"
                        description="Pelayan menulis draf, Admin meninjau dan menerbitkan."
                    />
                    {canCreate && (
                        <Button asChild>
                            <Link href={create()}>
                                <Plus />
                                Tulis renungan
                            </Link>
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

                <nav
                    aria-label="Status renungan"
                    className="flex flex-wrap gap-2"
                >
                    {tabs.map((item) => (
                        <Link
                            key={item.value}
                            href={index.url({ query: { tab: item.value } })}
                            preserveScroll
                            aria-current={
                                item.value === tab ? 'page' : undefined
                            }
                            className={cn(
                                'inline-flex items-center gap-2 rounded-full border px-4 py-1.5 text-sm font-medium transition-colors',
                                item.value === tab
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

                {devotionals.data.length === 0 ? (
                    <p className="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">
                        {emptyText[tab]}
                    </p>
                ) : (
                    <div className="overflow-x-auto rounded-xl border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50 text-left">
                                <tr>
                                    <th className="px-4 py-3 font-medium">
                                        Judul
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Tanggal terbit
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Penulis
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium">
                                        <span className="sr-only">Aksi</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {devotionals.data.map((row) => (
                                    <tr
                                        key={row.id}
                                        className="border-t align-top"
                                    >
                                        <td className="px-4 py-3">
                                            <div className="font-medium">
                                                {row.title}
                                            </div>
                                            <div className="text-muted-foreground">
                                                {row.scriptureRef}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 whitespace-nowrap">
                                            {formatDate(row.publishDate)}
                                            <Badge
                                                variant="secondary"
                                                className="ml-2"
                                            >
                                                {row.stateLabel}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3">
                                            {row.author ?? '-'}
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="flex flex-wrap justify-end gap-2">
                                                {tab === 'published' && (
                                                    <Button
                                                        asChild
                                                        size="sm"
                                                        variant="ghost"
                                                    >
                                                        <a
                                                            href={publicShow.url(
                                                                row.slug,
                                                            )}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                        >
                                                            <Eye />
                                                            Lihat
                                                        </a>
                                                    </Button>
                                                )}
                                                {row.can.update && (
                                                    <Button
                                                        asChild
                                                        size="sm"
                                                        variant="outline"
                                                    >
                                                        <Link
                                                            href={edit(row.id)}
                                                        >
                                                            <Pencil />
                                                            Ubah
                                                        </Link>
                                                    </Button>
                                                )}
                                                {row.can.publish &&
                                                    row.status === 'draft' && (
                                                        <Button
                                                            size="sm"
                                                            onClick={() =>
                                                                post(
                                                                    publish.url(
                                                                        row.id,
                                                                    ),
                                                                )
                                                            }
                                                        >
                                                            <Send />
                                                            Terbitkan
                                                        </Button>
                                                    )}
                                                {row.can.publish &&
                                                    row.status ===
                                                        'published' && (
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() =>
                                                                post(
                                                                    unpublish.url(
                                                                        row.id,
                                                                    ),
                                                                )
                                                            }
                                                        >
                                                            <Undo2 />
                                                            Jadikan draf
                                                        </Button>
                                                    )}
                                                {row.can.delete && (
                                                    <Button
                                                        size="sm"
                                                        variant="ghost"
                                                        onClick={() =>
                                                            remove(row)
                                                        }
                                                        aria-label={`Hapus ${row.title}`}
                                                    >
                                                        <Trash2 />
                                                    </Button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                {devotionals.last_page > 1 && (
                    <nav aria-label="Halaman" className="flex flex-wrap gap-1">
                        {devotionals.links.map((link) =>
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
        </>
    );
}

DevotionalsIndex.layout = {
    breadcrumbs: [{ title: 'Renungan', href: '/admin/devotionals' }],
};
