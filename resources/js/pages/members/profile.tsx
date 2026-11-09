import { Head, Link, router, useForm } from '@inertiajs/react';
import { ShieldCheck, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import type { ChangeEvent, FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
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
import { deletion, update } from '@/routes/members/profile';
import { edit as accountSettings } from '@/routes/profile';

type Props = {
    profile: MemberProfile;
    options: ProfileOptions;
    photoRules: { maxMb: number; minSize: number; maxSize: number };
    flash: string | null;
};

function ReadOnlyItem({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt className="text-sm text-muted-foreground">{label}</dt>
            <dd className="font-medium">{value}</dd>
        </div>
    );
}

/** The signed-in member's own profile (PRD 4.5). */
export default function Profile({
    profile,
    options,
    photoRules,
    flash,
}: Props) {
    const form = useForm<{
        name: string;
        nickname: string;
        phone: string;
        birth_date: string;
        gender: string;
        address: string;
        show_birthday: boolean;
        show_phone: boolean;
        photo: File | null;
        remove_photo: boolean;
    }>({
        name: profile.name,
        nickname: profile.nickname ?? '',
        phone: profile.phone ?? '',
        birth_date: profile.birthDate ?? '',
        gender: profile.gender ?? '',
        address: profile.address ?? '',
        show_birthday: profile.privacy.show_birthday,
        show_phone: profile.privacy.show_phone,
        photo: null,
        remove_photo: false,
    });

    const [preview, setPreview] = useState<string | null>(null);

    useEffect(() => {
        if (!form.data.photo) {
            setPreview(null);

            return;
        }

        const url = URL.createObjectURL(form.data.photo);
        setPreview(url);

        return () => URL.revokeObjectURL(url);
    }, [form.data.photo]);

    const shownPhoto =
        preview ?? (form.data.remove_photo ? null : profile.photoUrl);

    const pickPhoto = (event: ChangeEvent<HTMLInputElement>) =>
        form.setData((data) => ({
            ...data,
            photo: event.target.files?.[0] ?? null,
            remove_photo: false,
        }));

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(update.url(), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () =>
                form.setData((data) => ({
                    ...data,
                    photo: null,
                    remove_photo: false,
                })),
        });
    };

    return (
        <>
            <Head title="Profil Saya" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Profil Saya"
                    description="Data Anda hanya terlihat oleh Anda dan Admin cabang."
                />

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
                            Email diubah lewat{' '}
                            <Link
                                href={accountSettings()}
                                className="underline underline-offset-4"
                            >
                                pengaturan akun
                            </Link>{' '}
                            supaya bisa diverifikasi.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form
                            onSubmit={submit}
                            className="grid gap-8 lg:grid-cols-[14rem_1fr]"
                        >
                            <div className="flex flex-col items-center gap-3">
                                <MemberAvatar
                                    name={profile.name}
                                    src={shownPhoto}
                                    className="size-32 text-3xl"
                                />
                                <Label htmlFor="photo" className="sr-only">
                                    Foto profil
                                </Label>
                                <Input
                                    id="photo"
                                    type="file"
                                    accept=".jpg,.jpeg,.png,.webp"
                                    onChange={pickPhoto}
                                />
                                <p className="text-center text-xs text-muted-foreground">
                                    JPG, PNG, atau WebP, maks {photoRules.maxMb}{' '}
                                    MB, minimal {photoRules.minSize} ×{' '}
                                    {photoRules.minSize} px. Foto persegi paling
                                    pas.
                                </p>
                                <InputError message={form.errors.photo} />
                                {profile.photoUrl && !form.data.photo && (
                                    <div className="flex items-center gap-2">
                                        <Checkbox
                                            id="remove_photo"
                                            checked={form.data.remove_photo}
                                            onCheckedChange={(checked) =>
                                                form.setData(
                                                    'remove_photo',
                                                    checked === true,
                                                )
                                            }
                                        />
                                        <Label htmlFor="remove_photo">
                                            Hapus foto
                                        </Label>
                                    </div>
                                )}
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2 sm:col-span-2">
                                    <Label htmlFor="name">Nama lengkap</Label>
                                    <Input
                                        id="name"
                                        value={form.data.name}
                                        onChange={(e) =>
                                            form.setData('name', e.target.value)
                                        }
                                        required
                                        autoComplete="name"
                                    />
                                    <InputError message={form.errors.name} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="nickname">
                                        Nama panggilan
                                    </Label>
                                    <Input
                                        id="nickname"
                                        value={form.data.nickname}
                                        onChange={(e) =>
                                            form.setData(
                                                'nickname',
                                                e.target.value,
                                            )
                                        }
                                        maxLength={50}
                                        autoComplete="nickname"
                                    />
                                    <InputError
                                        message={form.errors.nickname}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="email">Email</Label>
                                    <Input
                                        id="email"
                                        value={profile.email}
                                        readOnly
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="phone">Nomor HP/WA</Label>
                                    <Input
                                        id="phone"
                                        type="tel"
                                        value={form.data.phone}
                                        onChange={(e) =>
                                            form.setData(
                                                'phone',
                                                e.target.value,
                                            )
                                        }
                                        autoComplete="tel"
                                    />
                                    <InputError message={form.errors.phone} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="birth_date">
                                        Tanggal lahir
                                    </Label>
                                    <Input
                                        id="birth_date"
                                        type="date"
                                        value={form.data.birth_date}
                                        onChange={(e) =>
                                            form.setData(
                                                'birth_date',
                                                e.target.value,
                                            )
                                        }
                                        autoComplete="bday"
                                    />
                                    <InputError
                                        message={form.errors.birth_date}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="gender">
                                        Jenis kelamin
                                    </Label>
                                    <select
                                        id="gender"
                                        value={form.data.gender}
                                        onChange={(e) =>
                                            form.setData(
                                                'gender',
                                                e.target.value,
                                            )
                                        }
                                        className={fieldClass}
                                    >
                                        <option value="">Pilih</option>
                                        {options.genders.map((option) => (
                                            <option
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={form.errors.gender} />
                                </div>
                                <div className="grid gap-2 sm:col-span-2">
                                    <Label htmlFor="address">Alamat</Label>
                                    <textarea
                                        id="address"
                                        value={form.data.address}
                                        onChange={(e) =>
                                            form.setData(
                                                'address',
                                                e.target.value,
                                            )
                                        }
                                        rows={3}
                                        maxLength={500}
                                        autoComplete="street-address"
                                        className={fieldClass}
                                    />
                                    <InputError message={form.errors.address} />
                                </div>

                                <fieldset className="grid gap-3 rounded-xl border p-4 sm:col-span-2">
                                    <legend className="flex items-center gap-2 px-1 text-sm font-medium">
                                        <ShieldCheck className="size-4 text-sky" />
                                        Privasi
                                    </legend>
                                    <div className="flex items-center gap-2">
                                        <Checkbox
                                            id="show_birthday"
                                            checked={form.data.show_birthday}
                                            onCheckedChange={(checked) =>
                                                form.setData(
                                                    'show_birthday',
                                                    checked === true,
                                                )
                                            }
                                        />
                                        <Label htmlFor="show_birthday">
                                            Tampilkan ulang tahun saya kepada
                                            jemaat lain
                                        </Label>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Checkbox
                                            id="show_phone"
                                            checked={form.data.show_phone}
                                            onCheckedChange={(checked) =>
                                                form.setData(
                                                    'show_phone',
                                                    checked === true,
                                                )
                                            }
                                        />
                                        <Label htmlFor="show_phone">
                                            Tampilkan nomor HP saya kepada
                                            jemaat lain
                                        </Label>
                                    </div>
                                </fieldset>

                                <div className="flex items-center gap-3 sm:col-span-2">
                                    <Button
                                        type="submit"
                                        disabled={form.processing}
                                    >
                                        {form.processing
                                            ? 'Menyimpan…'
                                            : 'Simpan profil'}
                                    </Button>
                                </div>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Keanggotaan</CardTitle>
                        <CardDescription>
                            Diisi oleh Admin. Hubungi sekretariat bila ada yang
                            keliru.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <dl className="grid gap-4 sm:grid-cols-4">
                            <ReadOnlyItem
                                label="Cabang"
                                value={profile.church ?? '-'}
                            />
                            <ReadOnlyItem
                                label="Status keanggotaan"
                                value={profile.membershipLabel}
                            />
                            <ReadOnlyItem
                                label="Tanggal bergabung"
                                value={formatDate(profile.joinedAt)}
                            />
                            <ReadOnlyItem
                                label="Baptis"
                                value={profile.baptized ? 'Sudah' : 'Belum'}
                            />
                        </dl>
                    </CardContent>
                </Card>

                <Card className="border-destructive/30">
                    <CardHeader>
                        <CardTitle>Hapus akun</CardTitle>
                        <CardDescription>
                            {profile.deletionRequestedAt
                                ? 'Permintaan Anda sedang ditinjau Admin. Akun tetap bisa dipakai sampai diproses.'
                                : 'Ajukan permintaan ke Admin untuk menghapus akun dan data pribadi Anda.'}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {profile.deletionRequestedAt ? (
                            <Button
                                variant="outline"
                                onClick={() =>
                                    router.delete(deletion.url(), {
                                        preserveScroll: true,
                                    })
                                }
                            >
                                Batalkan permintaan
                            </Button>
                        ) : (
                            <Button
                                variant="destructive"
                                onClick={() => {
                                    if (
                                        window.confirm(
                                            'Kirim permintaan penghapusan akun ke Admin?',
                                        )
                                    ) {
                                        router.post(
                                            deletion.url(),
                                            {},
                                            { preserveScroll: true },
                                        );
                                    }
                                }}
                            >
                                <Trash2 />
                                Minta hapus akun
                            </Button>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

Profile.layout = {
    breadcrumbs: [{ title: 'Profil Saya', href: '/profil' }],
};
