import { Head, Link, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { cn } from '@/lib/utils';
import { audit } from '@/routes/core';

type Log = {
    id: number;
    action: string;
    label: string;
    user: { name: string; email: string } | null;
    subject: string | null;
    metadata: string | null;
    ip: string | null;
    at: string;
};

type Props = {
    logs: {
        data: Log[];
        links: { url: string | null; label: string; active: boolean }[];
        last_page: number;
        total: number;
    };
    actions: { value: string; label: string }[];
    filters: { action: string | null };
};

const selectClass =
    'h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

/** Audit log for Superadmin (PRD 4.8, 4.13). */
export default function AuditLogPage({ logs, actions, filters }: Props) {
    return (
        <>
            <Head title="Audit Log" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Audit Log"
                        description={`Catatan akses dan perubahan data sensitif, terbaru di atas. ${logs.total} catatan.`}
                    />
                    <div>
                        <label htmlFor="action" className="sr-only">
                            Jenis aksi
                        </label>
                        <select
                            id="action"
                            className={selectClass}
                            value={filters.action ?? ''}
                            onChange={(event) =>
                                router.get(
                                    audit.url({
                                        query: event.target.value
                                            ? { action: event.target.value }
                                            : {},
                                    }),
                                    {},
                                    { preserveScroll: true },
                                )
                            }
                        >
                            <option value="">Semua aksi</option>
                            {actions.map((action) => (
                                <option key={action.value} value={action.value}>
                                    {action.label}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>

                {logs.data.length === 0 ? (
                    <p className="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">
                        Belum ada catatan.
                    </p>
                ) : (
                    <div className="overflow-x-auto rounded-xl border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50 text-left">
                                <tr>
                                    <th className="px-4 py-3 font-medium whitespace-nowrap">
                                        Waktu (WIB)
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Pengguna
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Aksi
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Data
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        IP
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {logs.data.map((log) => (
                                    <tr
                                        key={log.id}
                                        className="border-t align-top"
                                    >
                                        <td className="px-4 py-3 whitespace-nowrap tabular-nums">
                                            {log.at}
                                        </td>
                                        <td className="px-4 py-3">
                                            {log.user ? (
                                                <>
                                                    <div className="font-medium">
                                                        {log.user.name}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {log.user.email}
                                                    </div>
                                                </>
                                            ) : (
                                                <span className="text-muted-foreground">
                                                    Sistem
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3">
                                            <div>{log.label}</div>
                                            {log.subject && (
                                                <div className="text-xs text-muted-foreground">
                                                    {log.subject}
                                                </div>
                                            )}
                                        </td>
                                        <td className="max-w-md px-4 py-3">
                                            {log.metadata ? (
                                                <code className="text-xs break-all text-muted-foreground">
                                                    {log.metadata}
                                                </code>
                                            ) : (
                                                '-'
                                            )}
                                        </td>
                                        <td className="px-4 py-3 whitespace-nowrap text-muted-foreground">
                                            {log.ip ?? '-'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                {logs.last_page > 1 && (
                    <nav aria-label="Halaman" className="flex flex-wrap gap-1">
                        {logs.links.map((link) =>
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

AuditLogPage.layout = {
    breadcrumbs: [{ title: 'Audit Log', href: '/admin/audit-log' }],
};
