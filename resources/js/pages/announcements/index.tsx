import { Head, Link, router } from '@inertiajs/react';
import { ExternalLink, Pencil, Pin, Plus, Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { create, destroy, edit } from '@/routes/announcements';

type Row = {
    id: number;
    title: string;
    excerpt: string;
    isPinned: boolean;
    state: 'Draf' | 'Terjadwal' | 'Berakhir' | 'Tayang';
    publishedLabel: string;
    expiresLabel: string | null;
    url: string;
};

type Props = {
    announcements: Row[];
    flash: string | null;
};

/** Admin › Pengumuman: announcements of the branch, pinned first. */
export default function AnnouncementsIndex({ announcements, flash }: Props) {
    return (
        <>
            <Head title="Pengumuman" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Pengumuman"
                        description="Tampil di halaman Pengumuman dari tanggal tayang sampai tanggal berakhir. Yang disematkan tampil paling atas."
                    />
                    <Button asChild>
                        <Link href={create()}>
                            <Plus />
                            Pengumuman baru
                        </Link>
                    </Button>
                </div>

                {flash && (
                    <p
                        role="status"
                        className="rounded-xl border border-green-600/30 bg-green-600/10 px-4 py-3 text-sm"
                    >
                        {flash}
                    </p>
                )}

                {announcements.length === 0 ? (
                    <p className="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">
                        Belum ada pengumuman.
                    </p>
                ) : (
                    <ul className="grid gap-3">
                        {announcements.map((item) => (
                            <li
                                key={item.id}
                                className="flex flex-col gap-4 rounded-2xl border bg-card p-4 sm:flex-row sm:items-center"
                            >
                                <div className="min-w-0 flex-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <Badge
                                            variant={
                                                item.state === 'Tayang'
                                                    ? 'default'
                                                    : 'outline'
                                            }
                                        >
                                            {item.state}
                                        </Badge>
                                        {item.isPinned && (
                                            <Badge variant="secondary">
                                                <Pin className="size-3" />
                                                Disematkan
                                            </Badge>
                                        )}
                                        <span className="text-xs text-muted-foreground">
                                            {item.publishedLabel}
                                            {item.expiresLabel &&
                                                ` – ${item.expiresLabel}`}
                                        </span>
                                    </div>
                                    <p className="mt-2 truncate font-display text-lg font-bold tracking-tight">
                                        {item.title}
                                    </p>
                                    <p className="line-clamp-1 text-sm text-muted-foreground">
                                        {item.excerpt}
                                    </p>
                                </div>
                                <div className="flex items-center gap-1">
                                    {item.state === 'Tayang' && (
                                        <Button
                                            asChild
                                            size="icon"
                                            variant="ghost"
                                            aria-label="Lihat di situs"
                                        >
                                            <a
                                                href={item.url}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                            >
                                                <ExternalLink />
                                            </a>
                                        </Button>
                                    )}
                                    <Button
                                        asChild
                                        size="icon"
                                        variant="ghost"
                                        aria-label={`Ubah ${item.title}`}
                                    >
                                        <Link href={edit(item.id)}>
                                            <Pencil />
                                        </Link>
                                    </Button>
                                    <Button
                                        type="button"
                                        size="icon"
                                        variant="ghost"
                                        aria-label={`Hapus ${item.title}`}
                                        onClick={() => {
                                            if (
                                                window.confirm(
                                                    `Hapus pengumuman "${item.title}"?`,
                                                )
                                            ) {
                                                router.delete(
                                                    destroy.url(item.id),
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
            </div>
        </>
    );
}

AnnouncementsIndex.layout = {
    breadcrumbs: [{ title: 'Pengumuman', href: '/admin/announcements' }],
};
