import { Head, Link } from '@inertiajs/react';
import { Pencil, Plus, ScrollText } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { ChurchDialog } from '@/modules/core/church-dialog';
import type { ChurchRecord } from '@/modules/core/church-dialog';
import { audit } from '@/routes/core';

type Role = {
    name: string;
    label: string;
    permissions: string[];
};

type Props = {
    churches: ChurchRecord[];
    roles: Role[];
    canManageChurches: boolean;
    canViewAudit: boolean;
    flash: string | null;
};

export default function CoreIndex({
    churches,
    roles,
    canManageChurches,
    canViewAudit,
    flash,
}: Props) {
    const [editing, setEditing] = useState<ChurchRecord | null>(null);
    const [creating, setCreating] = useState(false);

    return (
        <>
            <Head title="Cabang & Akses" />
            <div className="flex flex-1 flex-col gap-8 p-4">
                {flash && (
                    <p
                        role="status"
                        className="rounded-xl border border-green-600/30 bg-green-600/10 px-4 py-3 text-sm"
                    >
                        {flash}
                    </p>
                )}

                <section className="flex flex-col gap-4">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <Heading
                            title="Cabang"
                            description="Cabang yang dapat Anda kelola. Data inti setiap cabang terpisah."
                        />
                        <div className="flex flex-wrap gap-2">
                            {canViewAudit && (
                                <Button asChild variant="outline">
                                    <Link href={audit()}>
                                        <ScrollText />
                                        Audit log
                                    </Link>
                                </Button>
                            )}
                            {canManageChurches && (
                                <Button onClick={() => setCreating(true)}>
                                    <Plus />
                                    Cabang baru
                                </Button>
                            )}
                        </div>
                    </div>
                    {churches.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Belum ada cabang.
                        </p>
                    ) : (
                        <div className="grid gap-4 md:grid-cols-2">
                            {churches.map((church) => (
                                <Card key={church.id}>
                                    <CardHeader>
                                        <div className="flex items-start justify-between gap-2">
                                            <CardTitle>{church.name}</CardTitle>
                                            <div className="flex items-center gap-1">
                                                {church.isDefault && (
                                                    <Badge variant="outline">
                                                        Utama
                                                    </Badge>
                                                )}
                                                <Badge
                                                    variant={
                                                        church.isActive
                                                            ? 'secondary'
                                                            : 'outline'
                                                    }
                                                >
                                                    {church.isActive
                                                        ? 'Aktif'
                                                        : 'Nonaktif'}
                                                </Badge>
                                            </div>
                                        </div>
                                        <CardDescription>
                                            {church.address ??
                                                'Alamat belum diisi'}
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="flex flex-wrap items-center gap-x-6 gap-y-1 text-sm text-muted-foreground">
                                        <span>
                                            {church.usersCount} akun terdaftar
                                        </span>
                                        {church.whatsapp && (
                                            <span>WA {church.whatsapp}</span>
                                        )}
                                        {canManageChurches && (
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                className="ml-auto"
                                                onClick={() =>
                                                    setEditing(church)
                                                }
                                            >
                                                <Pencil />
                                                Ubah
                                            </Button>
                                        )}
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    )}
                </section>

                <section className="flex flex-col gap-4">
                    <Heading
                        title="Role dan hak akses"
                        description="Hak akses setiap role. Superadmin bekerja lintas cabang, role lain hanya di cabangnya."
                    />
                    <div className="overflow-x-auto rounded-xl border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50 text-left">
                                <tr>
                                    <th className="px-4 py-3 font-medium">
                                        Role
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Hak akses
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {roles.map((role) => (
                                    <tr
                                        key={role.name}
                                        className="border-t align-top"
                                    >
                                        <td className="px-4 py-3 font-medium whitespace-nowrap">
                                            {role.label}
                                        </td>
                                        <td className="px-4 py-3">
                                            <RolePermissions
                                                permissions={role.permissions}
                                            />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <ChurchDialog
                key={creating ? 'new' : (editing?.id ?? 'none')}
                church={editing}
                open={creating || editing !== null}
                onClose={() => {
                    setCreating(false);
                    setEditing(null);
                }}
            />
        </>
    );
}

function RolePermissions({ permissions }: { permissions: string[] }) {
    if (permissions.includes('*')) {
        return <span>Semua hak akses</span>;
    }

    if (permissions.length === 0) {
        return (
            <span className="text-muted-foreground">
                Hanya data miliknya sendiri
            </span>
        );
    }

    return (
        <div className="flex flex-wrap gap-1">
            {permissions.map((permission) => (
                <Badge key={permission} variant="outline">
                    <code>{permission}</code>
                </Badge>
            ))}
        </div>
    );
}

CoreIndex.layout = {
    breadcrumbs: [{ title: 'Cabang & Akses', href: '/admin/core' }],
};
