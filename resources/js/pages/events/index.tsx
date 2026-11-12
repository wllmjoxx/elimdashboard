import { Head, Link, router } from '@inertiajs/react';
import { ExternalLink, Pencil, Plus, Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { PublicEvent } from '@/modules/events/types';
import { create, destroy, edit } from '@/routes/events';

type Row = PublicEvent & { isPublished: boolean };

type Props = {
    events: Row[];
    flash: string | null;
};

const stateVariant = {
    ongoing: 'default',
    upcoming: 'secondary',
    past: 'outline',
} as const;

/** Admin › Kegiatan: every event of the branch, newest first. */
export default function EventsIndex({ events, flash }: Props) {
    return (
        <>
            <Head title="Kegiatan" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Kegiatan"
                        description="Tampil di Beranda dan halaman Kegiatan: yang sedang berlangsung dan yang akan datang."
                    />
                    <Button asChild>
                        <Link href={create()}>
                            <Plus />
                            Kegiatan baru
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

                {events.length === 0 ? (
                    <p className="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">
                        Belum ada kegiatan.
                    </p>
                ) : (
                    <ul className="grid gap-3">
                        {events.map((event) => (
                            <li
                                key={event.id}
                                className="flex flex-col gap-4 rounded-2xl border bg-card p-4 sm:flex-row sm:items-center"
                            >
                                <div className="min-w-0 flex-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <Badge
                                            variant={stateVariant[event.state]}
                                        >
                                            {event.stateLabel}
                                        </Badge>
                                        {!event.isPublished && (
                                            <Badge variant="outline">
                                                Disembunyikan
                                            </Badge>
                                        )}
                                    </div>
                                    <p className="mt-2 truncate font-display text-lg font-bold tracking-tight">
                                        {event.title}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {event.dateLabel} · {event.timeLabel}
                                        {event.location &&
                                            ` · ${event.location}`}
                                    </p>
                                </div>
                                <div className="flex items-center gap-1">
                                    {event.isPublished && (
                                        <Button
                                            asChild
                                            size="icon"
                                            variant="ghost"
                                            aria-label="Lihat di situs"
                                        >
                                            <a
                                                href={event.url}
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
                                        aria-label={`Ubah ${event.title}`}
                                    >
                                        <Link href={edit(event.id)}>
                                            <Pencil />
                                        </Link>
                                    </Button>
                                    <Button
                                        type="button"
                                        size="icon"
                                        variant="ghost"
                                        aria-label={`Hapus ${event.title}`}
                                        onClick={() => {
                                            if (
                                                window.confirm(
                                                    `Hapus kegiatan "${event.title}"?`,
                                                )
                                            ) {
                                                router.delete(
                                                    destroy.url(event.id),
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

EventsIndex.layout = {
    breadcrumbs: [{ title: 'Kegiatan', href: '/admin/events' }],
};
