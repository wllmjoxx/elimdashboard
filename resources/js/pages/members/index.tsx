import { Head, Link, router } from '@inertiajs/react';
import { Check, MessageSquareMore, Power, X } from 'lucide-react';
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
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { InviteDialog } from '@/modules/members/invite-dialog';
import type { InviteOptions } from '@/modules/members/invite-dialog';
import {
    approve,
    index,
    reject,
    requestInfo,
    show,
    status as statusRoute,
} from '@/routes/members';

type Status = 'pending' | 'active' | 'rejected' | 'inactive';

type Member = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    church: string | null;
    roles: string[];
    reviewNote: string | null;
    deletionRequested: boolean;
    registeredAt: string | null;
    approvedAt: string | null;
    can: { review: boolean; deactivate: boolean };
};

type Props = {
    status: Status;
    statuses: { value: Status; label: string; count: number }[];
    members: {
        data: Member[];
        links: { url: string | null; label: string; active: boolean }[];
        total: number;
    };
    showChurch: boolean;
    flash: string | null;
    invite: InviteOptions | null;
};

type NoteAction = { kind: 'reject' | 'request-info'; member: Member };

const dateFormat = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
});

function formatDate(value: string | null): string {
    return value ? dateFormat.format(new Date(value)) : '-';
}

/** Dialog asking for the rejection reason or the missing data. */
function NoteDialog({
    action,
    onClose,
}: {
    action: NoteAction | null;
    onClose: () => void;
}) {
    const [note, setNote] = useState('');
    const [error, setError] = useState<string>();
    const [processing, setProcessing] = useState(false);
    const isReject = action?.kind === 'reject';

    const submit = () => {
        if (!action) {
            return;
        }

        const route = isReject ? reject : requestInfo;

        router.post(
            route.url(action.member.id),
            { note },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onError: (errors) => setError(errors.note),
                onSuccess: () => {
                    setNote('');
                    setError(undefined);
                    onClose();
                },
            },
        );
    };

    return (
        <Dialog
            open={action !== null}
            onOpenChange={(open) => !open && onClose()}
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {isReject ? 'Tolak pendaftaran' : 'Minta lengkapi data'}
                    </DialogTitle>
                    <DialogDescription>
                        {isReject
                            ? `Alasan akan dikirim lewat email ke ${action?.member.name}.`
                            : `Pesan akan dikirim lewat email ke ${action?.member.name}. Pendaftaran tetap menunggu.`}
                    </DialogDescription>
                </DialogHeader>
                <div className="grid gap-2">
                    <Label htmlFor="note">
                        {isReject ? 'Alasan' : 'Pesan'}
                    </Label>
                    <textarea
                        id="note"
                        value={note}
                        onChange={(event) => setNote(event.target.value)}
                        rows={4}
                        maxLength={1000}
                        className="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        placeholder={
                            isReject
                                ? 'Contoh: data tidak dapat kami verifikasi.'
                                : 'Contoh: mohon kirim nomor HP yang aktif.'
                        }
                    />
                    <InputError message={error} />
                </div>
                <DialogFooter>
                    <Button variant="outline" onClick={onClose}>
                        Batal
                    </Button>
                    <Button
                        variant={isReject ? 'destructive' : 'default'}
                        onClick={submit}
                        disabled={processing || note.trim() === ''}
                    >
                        {isReject ? 'Tolak' : 'Kirim'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

export default function MembersIndex({
    status,
    statuses,
    members,
    showChurch,
    flash,
    invite,
}: Props) {
    const [noteAction, setNoteAction] = useState<NoteAction | null>(null);

    const post = (url: string, data: Record<string, string> = {}) =>
        router.post(url, data, { preserveScroll: true });

    return (
        <>
            <Head title="Jemaat" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Jemaat"
                        description="Tinjau pendaftaran dan kelola akun jemaat cabang Anda."
                    />
                    {invite && <InviteDialog options={invite} />}
                </div>

                {flash && (
                    <p
                        role="status"
                        className="rounded-xl border border-green-600/30 bg-green-600/10 px-4 py-3 text-sm"
                    >
                        {flash}
                    </p>
                )}

                <nav aria-label="Status akun" className="flex flex-wrap gap-2">
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

                {members.data.length === 0 ? (
                    <p className="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">
                        Tidak ada akun dengan status ini.
                    </p>
                ) : (
                    <div className="overflow-x-auto rounded-xl border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50 text-left">
                                <tr>
                                    <th className="px-4 py-3 font-medium">
                                        Nama
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Kontak
                                    </th>
                                    {showChurch && (
                                        <th className="px-4 py-3 font-medium">
                                            Cabang
                                        </th>
                                    )}
                                    <th className="px-4 py-3 font-medium">
                                        {status === 'pending'
                                            ? 'Mendaftar'
                                            : 'Disetujui'}
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium">
                                        <span className="sr-only">Aksi</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {members.data.map((member) => (
                                    <tr
                                        key={member.id}
                                        className="border-t align-top"
                                    >
                                        <td className="px-4 py-3">
                                            <Link
                                                href={show.url(member.id)}
                                                className="font-medium underline-offset-4 hover:underline"
                                            >
                                                {member.name}
                                            </Link>
                                            {member.deletionRequested && (
                                                <Badge
                                                    variant="destructive"
                                                    className="ml-2"
                                                >
                                                    Minta dihapus
                                                </Badge>
                                            )}
                                            {member.roles.length > 0 && (
                                                <div className="mt-1 flex flex-wrap gap-1">
                                                    {member.roles.map(
                                                        (role) => (
                                                            <Badge
                                                                key={role}
                                                                variant="secondary"
                                                            >
                                                                {role}
                                                            </Badge>
                                                        ),
                                                    )}
                                                </div>
                                            )}
                                            {member.reviewNote && (
                                                <p className="mt-1 max-w-sm text-xs text-muted-foreground">
                                                    Catatan: {member.reviewNote}
                                                </p>
                                            )}
                                        </td>
                                        <td className="px-4 py-3">
                                            <div>{member.email}</div>
                                            <div className="text-muted-foreground">
                                                {member.phone ?? '-'}
                                            </div>
                                        </td>
                                        {showChurch && (
                                            <td className="px-4 py-3">
                                                {member.church ?? '-'}
                                            </td>
                                        )}
                                        <td className="px-4 py-3 whitespace-nowrap">
                                            {formatDate(
                                                status === 'pending'
                                                    ? member.registeredAt
                                                    : member.approvedAt,
                                            )}
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="flex flex-wrap justify-end gap-2">
                                                {status === 'pending' &&
                                                    member.can.review && (
                                                        <>
                                                            <Button
                                                                size="sm"
                                                                onClick={() =>
                                                                    post(
                                                                        approve.url(
                                                                            member.id,
                                                                        ),
                                                                    )
                                                                }
                                                            >
                                                                <Check />
                                                                Setujui
                                                            </Button>
                                                            <Button
                                                                size="sm"
                                                                variant="outline"
                                                                onClick={() =>
                                                                    setNoteAction(
                                                                        {
                                                                            kind: 'request-info',
                                                                            member,
                                                                        },
                                                                    )
                                                                }
                                                            >
                                                                <MessageSquareMore />
                                                                Minta data
                                                            </Button>
                                                            <Button
                                                                size="sm"
                                                                variant="outline"
                                                                onClick={() =>
                                                                    setNoteAction(
                                                                        {
                                                                            kind: 'reject',
                                                                            member,
                                                                        },
                                                                    )
                                                                }
                                                            >
                                                                <X />
                                                                Tolak
                                                            </Button>
                                                        </>
                                                    )}
                                                {status === 'active' &&
                                                    member.can.deactivate && (
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() =>
                                                                post(
                                                                    statusRoute.url(
                                                                        member.id,
                                                                    ),
                                                                    {
                                                                        status: 'inactive',
                                                                    },
                                                                )
                                                            }
                                                        >
                                                            <Power />
                                                            Nonaktifkan
                                                        </Button>
                                                    )}
                                                {status === 'inactive' &&
                                                    member.can.deactivate && (
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() =>
                                                                post(
                                                                    statusRoute.url(
                                                                        member.id,
                                                                    ),
                                                                    {
                                                                        status: 'active',
                                                                    },
                                                                )
                                                            }
                                                        >
                                                            <Power />
                                                            Aktifkan
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

                {members.links.length > 3 && (
                    <nav aria-label="Halaman" className="flex flex-wrap gap-1">
                        {members.links.map((link) =>
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

            <NoteDialog
                action={noteAction}
                onClose={() => setNoteAction(null)}
            />
        </>
    );
}

MembersIndex.layout = {
    breadcrumbs: [{ title: 'Jemaat', href: '/admin/members' }],
};
