import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { MemberAvatar } from '@/modules/members/avatar';
import type { MemberProfile, ProfileOptions } from '@/modules/members/types';
import { fieldClass, formatDate } from '@/modules/members/types';
import { membership } from '@/routes/members';

type Props = {
    profile: MemberProfile;
    options: ProfileOptions;
    can: { updateMembership: boolean };
    flash: string | null;
};

function Item({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt className="text-sm text-muted-foreground">{label}</dt>
            <dd className="font-medium whitespace-pre-line">{value}</dd>
        </div>
    );
}

/** Admin view of one member (PRD 4.5, 4.8). */
export default function MemberShow({ profile, options, can, flash }: Props) {
    const form = useForm({
        membership_status: profile.membershipStatus,
        joined_at: profile.joinedAt ?? '',
        baptized: profile.baptized,
    });

    const gender =
        options.genders.find((option) => option.value === profile.gender)
            ?.label ?? '-';

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(membership.url(profile.id), { preserveScroll: true });
    };

    return (
        <>
            <Head title={profile.name} />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex items-center gap-4">
                    <MemberAvatar
                        name={profile.name}
                        src={profile.photoUrl}
                        className="size-16 text-xl"
                    />
                    <div className="flex-1">
                        <Heading
                            title={profile.name}
                            description={profile.email}
                        />
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Badge variant="secondary">{profile.statusLabel}</Badge>
                        {profile.deletionRequestedAt && (
                            <Badge variant="destructive">
                                Minta akun dihapus
                            </Badge>
                        )}
                    </div>
                </div>

                {flash && (
                    <p
                        role="status"
                        className="rounded-xl border border-green-600/30 bg-green-600/10 px-4 py-3 text-sm"
                    >
                        {flash}
                    </p>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>Data pribadi</CardTitle>
                        <CardDescription>
                            Diisi oleh jemaat sendiri.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <dl className="grid gap-4 sm:grid-cols-3">
                            <Item
                                label="Nama panggilan"
                                value={profile.nickname ?? '-'}
                            />
                            <Item
                                label="Nomor HP/WA"
                                value={profile.phone ?? '-'}
                            />
                            <Item
                                label="Cabang"
                                value={profile.church ?? '-'}
                            />
                            <Item
                                label="Tanggal lahir"
                                value={formatDate(profile.birthDate)}
                            />
                            <Item label="Jenis kelamin" value={gender} />
                            <Item
                                label="Alamat"
                                value={profile.address ?? '-'}
                            />
                        </dl>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Keanggotaan</CardTitle>
                        <CardDescription>Dicatat oleh Admin.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {can.updateMembership ? (
                            <form
                                onSubmit={submit}
                                className="grid gap-4 sm:grid-cols-3"
                            >
                                <div className="grid gap-2">
                                    <Label htmlFor="membership_status">
                                        Status keanggotaan
                                    </Label>
                                    <select
                                        id="membership_status"
                                        value={form.data.membership_status}
                                        onChange={(e) =>
                                            form.setData(
                                                'membership_status',
                                                e.target.value,
                                            )
                                        }
                                        className={fieldClass}
                                    >
                                        {options.membershipStatuses.map(
                                            (option) => (
                                                <option
                                                    key={option.value}
                                                    value={option.value}
                                                >
                                                    {option.label}
                                                </option>
                                            ),
                                        )}
                                    </select>
                                    <InputError
                                        message={form.errors.membership_status}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="joined_at">
                                        Tanggal bergabung
                                    </Label>
                                    <Input
                                        id="joined_at"
                                        type="date"
                                        value={form.data.joined_at}
                                        onChange={(e) =>
                                            form.setData(
                                                'joined_at',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.joined_at}
                                    />
                                </div>
                                <div className="flex items-center gap-2 sm:pt-6">
                                    <Checkbox
                                        id="baptized"
                                        checked={form.data.baptized}
                                        onCheckedChange={(checked) =>
                                            form.setData(
                                                'baptized',
                                                checked === true,
                                            )
                                        }
                                    />
                                    <Label htmlFor="baptized">
                                        Sudah dibaptis
                                    </Label>
                                </div>
                                <div className="flex items-center gap-3 sm:col-span-3">
                                    <Button
                                        type="submit"
                                        disabled={form.processing}
                                    >
                                        Simpan
                                    </Button>
                                    {form.recentlySuccessful && (
                                        <p className="text-sm text-muted-foreground">
                                            Tersimpan.
                                        </p>
                                    )}
                                </div>
                            </form>
                        ) : (
                            <dl className="grid gap-4 sm:grid-cols-3">
                                <Item
                                    label="Status keanggotaan"
                                    value={profile.membershipLabel}
                                />
                                <Item
                                    label="Tanggal bergabung"
                                    value={formatDate(profile.joinedAt)}
                                />
                                <Item
                                    label="Baptis"
                                    value={profile.baptized ? 'Sudah' : 'Belum'}
                                />
                            </dl>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

MemberShow.layout = {
    breadcrumbs: [{ title: 'Jemaat', href: '/admin/members' }],
};
