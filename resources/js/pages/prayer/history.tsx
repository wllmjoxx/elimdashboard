import { Head, Link } from '@inertiajs/react';
import { HandHeart } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { formatSentAt } from '@/modules/prayer/types';
import { prayer as prayerForm } from '@/routes/landing';

type Props = {
    requests: {
        data: {
            id: number;
            body: string;
            category: string;
            status: string;
            statusLabel: string;
            sentAt: string;
        }[];
        links: { url: string | null; label: string; active: boolean }[];
        last_page: number;
    };
    remaining: number;
    flash: string | null;
};

/** A member's own prayer requests and their status (PRD 4.6). */
export default function PrayerHistory({ requests, remaining, flash }: Props) {
    return (
        <>
            <Head title="Riwayat Doa" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Riwayat Doa"
                        description={`Pokok doa yang pernah Anda kirim. Sisa kiriman minggu ini: ${remaining}.`}
                    />
                    {remaining > 0 && (
                        <Button asChild>
                            <Link href={prayerForm()}>
                                <HandHeart />
                                Kirim pokok doa
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

                {requests.data.length === 0 ? (
                    <p className="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">
                        Anda belum pernah mengirim pokok doa.
                    </p>
                ) : (
                    <ul className="grid gap-3">
                        {requests.data.map((item) => (
                            <li key={item.id} className="rounded-xl border p-4">
                                <div className="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                                    <span>{formatSentAt(item.sentAt)}</span>
                                    <span>·</span>
                                    <span>{item.category}</span>
                                    <Badge
                                        variant={
                                            item.status === 'new'
                                                ? 'outline'
                                                : 'secondary'
                                        }
                                        className="ml-auto"
                                    >
                                        {item.statusLabel}
                                    </Badge>
                                </div>
                                <p className="mt-2 whitespace-pre-line">
                                    {item.body}
                                </p>
                            </li>
                        ))}
                    </ul>
                )}

                {requests.last_page > 1 && (
                    <nav aria-label="Halaman" className="flex flex-wrap gap-1">
                        {requests.links.map((link) =>
                            link.url ? (
                                <Link
                                    key={link.label}
                                    href={link.url}
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

PrayerHistory.layout = {
    breadcrumbs: [{ title: 'Riwayat Doa', href: '/doa/riwayat' }],
};
