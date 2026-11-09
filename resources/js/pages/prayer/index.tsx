import { Head, Link, router } from '@inertiajs/react';
import {
    Archive,
    Check,
    ChevronLeft,
    ChevronRight,
    Printer,
    Sparkles,
    Trash2,
} from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { Option, PrayerRow, Week } from '@/modules/prayer/types';
import { formatSentAt } from '@/modules/prayer/types';
import { destroy, index, print, status as statusRoute } from '@/routes/prayer';

type Row = PrayerRow & { can: { mark: boolean; delete: boolean } };

type Props = {
    week: Week;
    filters: { status: string | null; category: string | null };
    requests: Row[];
    statuses: (Option & { count: number })[];
    categories: Option[];
    canExport: boolean;
    flash: string | null;
};

const selectClass =
    'h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

/** Tim Doa panel: prayer requests of one week (PRD 4.6). */
export default function PrayerIndex({
    week,
    filters,
    requests,
    statuses,
    categories,
    canExport,
    flash,
}: Props) {
    const query = (changes: Record<string, string | null>) => {
        const next = { week: week.start, ...filters, ...changes };

        return Object.fromEntries(
            Object.entries(next).filter(([, value]) => value),
        ) as Record<string, string>;
    };

    const visit = (changes: Record<string, string | null>) =>
        router.get(
            index.url({ query: query(changes) }),
            {},
            { preserveScroll: true, preserveState: true },
        );

    const mark = (row: Row, status: PrayerRow['status']) =>
        router.post(
            statusRoute.url(row.id),
            { status },
            { preserveScroll: true },
        );

    const remove = (row: Row) => {
        if (
            window.confirm(
                `Hapus pokok doa dari ${row.fullName}? Tindakan ini tidak dapat dibatalkan.`,
            )
        ) {
            router.delete(destroy.url(row.id), { preserveScroll: true });
        }
    };

    return (
        <>
            <Head title="Doa Mingguan" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Doa Mingguan"
                        description="Pokok doa jemaat dan tamu, dikelompokkan per minggu (Senin–Minggu WIB). Bersifat rahasia."
                    />
                    {canExport && (
                        <Button asChild variant="outline">
                            <a
                                href={print.url({ query: query({}) })}
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <Printer />
                                Cetak / PDF
                            </a>
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

                <div className="flex flex-wrap items-center gap-3">
                    <div className="flex items-center gap-1 rounded-full border p-1">
                        <Button
                            size="icon"
                            variant="ghost"
                            className="rounded-full"
                            onClick={() => visit({ week: week.previous })}
                            aria-label="Minggu sebelumnya"
                        >
                            <ChevronLeft />
                        </Button>
                        <span className="min-w-40 text-center text-sm font-medium">
                            {week.label}
                            {week.isCurrent && (
                                <span className="text-muted-foreground">
                                    {' '}
                                    · minggu ini
                                </span>
                            )}
                        </span>
                        <Button
                            size="icon"
                            variant="ghost"
                            className="rounded-full"
                            onClick={() => visit({ week: week.next })}
                            disabled={!week.hasNext}
                            aria-label="Minggu berikutnya"
                        >
                            <ChevronRight />
                        </Button>
                    </div>

                    <label className="sr-only" htmlFor="filter-status">
                        Status
                    </label>
                    <select
                        id="filter-status"
                        className={selectClass}
                        value={filters.status ?? ''}
                        onChange={(e) =>
                            visit({ status: e.target.value || null })
                        }
                    >
                        <option value="">Semua status</option>
                        {statuses.map((item) => (
                            <option key={item.value} value={item.value}>
                                {item.label} ({item.count})
                            </option>
                        ))}
                    </select>

                    <label className="sr-only" htmlFor="filter-category">
                        Kategori
                    </label>
                    <select
                        id="filter-category"
                        className={selectClass}
                        value={filters.category ?? ''}
                        onChange={(e) =>
                            visit({ category: e.target.value || null })
                        }
                    >
                        <option value="">Semua kategori</option>
                        {categories.map((item) => (
                            <option key={item.value} value={item.value}>
                                {item.label}
                            </option>
                        ))}
                    </select>
                </div>

                {requests.length === 0 ? (
                    <p className="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">
                        Tidak ada pokok doa untuk pilihan ini.
                    </p>
                ) : (
                    <ul className="grid gap-4 lg:grid-cols-2">
                        {requests.map((row) => (
                            <li
                                key={row.id}
                                className={cn(
                                    'flex flex-col rounded-xl border p-5',
                                    row.status === 'archived' && 'opacity-60',
                                )}
                            >
                                <div className="flex flex-wrap items-start justify-between gap-2">
                                    <div>
                                        <div className="font-semibold">
                                            {row.fullName}
                                            {row.age && (
                                                <span className="font-normal text-muted-foreground">
                                                    {' '}
                                                    · {row.age} th
                                                </span>
                                            )}
                                        </div>
                                        <div className="text-sm text-muted-foreground">
                                            {[row.phone, row.address]
                                                .filter(Boolean)
                                                .join(' · ') || 'Tanpa kontak'}
                                        </div>
                                    </div>
                                    <div className="flex flex-wrap gap-1">
                                        <Badge variant="outline">
                                            {row.category}
                                        </Badge>
                                        <Badge
                                            variant={
                                                row.status === 'new'
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                        >
                                            {row.statusLabel}
                                        </Badge>
                                    </div>
                                </div>
                                <p className="mt-3 flex-1 whitespace-pre-line">
                                    {row.body}
                                </p>
                                <div className="mt-3 text-xs text-muted-foreground">
                                    {formatSentAt(row.sentAt)} ·{' '}
                                    {row.isMember ? 'Jemaat' : 'Tamu'}
                                    {row.prayedBy &&
                                        ` · didoakan oleh ${row.prayedBy}`}
                                </div>
                                {(row.can.mark || row.can.delete) && (
                                    <div className="mt-4 flex flex-wrap gap-2 border-t pt-4">
                                        {row.can.mark &&
                                            row.status === 'new' && (
                                                <Button
                                                    size="sm"
                                                    onClick={() =>
                                                        mark(row, 'prayed')
                                                    }
                                                >
                                                    <Check />
                                                    Didoakan
                                                </Button>
                                            )}
                                        {row.can.mark &&
                                            row.status !== 'answered' && (
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() =>
                                                        mark(row, 'answered')
                                                    }
                                                >
                                                    <Sparkles />
                                                    Terjawab
                                                </Button>
                                            )}
                                        {row.can.mark &&
                                            row.status !== 'archived' && (
                                                <Button
                                                    size="sm"
                                                    variant="ghost"
                                                    onClick={() =>
                                                        mark(row, 'archived')
                                                    }
                                                >
                                                    <Archive />
                                                    Arsipkan
                                                </Button>
                                            )}
                                        {row.can.delete && (
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                className="ml-auto"
                                                onClick={() => remove(row)}
                                                aria-label={`Hapus pokok doa dari ${row.fullName}`}
                                            >
                                                <Trash2 />
                                            </Button>
                                        )}
                                    </div>
                                )}
                            </li>
                        ))}
                    </ul>
                )}

                <p className="text-xs text-muted-foreground">
                    Setiap kali halaman ini dibuka, dicetak, atau diubah,
                    aksinya dicatat di audit log.{' '}
                    <Link
                        href={index.url()}
                        className="underline underline-offset-4"
                    >
                        Kembali ke minggu ini
                    </Link>
                </p>
            </div>
        </>
    );
}

PrayerIndex.layout = {
    breadcrumbs: [{ title: 'Doa Mingguan', href: '/admin/prayer' }],
};
