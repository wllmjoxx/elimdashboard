import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { store, update } from '@/routes/core/churches';

export type ChurchRecord = {
    id: number;
    name: string;
    slug: string;
    address: string | null;
    whatsapp: string | null;
    email: string | null;
    timezone: string;
    isActive: boolean;
    isDefault: boolean;
    usersCount: number;
};

const timezones = [
    { value: 'Asia/Jakarta', label: 'WIB (Asia/Jakarta)' },
    { value: 'Asia/Makassar', label: 'WITA (Asia/Makassar)' },
    { value: 'Asia/Jayapura', label: 'WIT (Asia/Jayapura)' },
];

const selectClass =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

/** Add or edit a branch (Superadmin, PRD 4.10). */
export function ChurchDialog({
    church,
    open,
    onClose,
}: {
    church: ChurchRecord | null;
    open: boolean;
    onClose: () => void;
}) {
    const form = useForm({
        name: church?.name ?? '',
        slug: church?.slug ?? '',
        address: church?.address ?? '',
        whatsapp: church?.whatsapp ?? '',
        email: church?.email ?? '',
        timezone: church?.timezone ?? 'Asia/Jakarta',
        is_active: church?.isActive ?? true,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: onClose };

        if (church) {
            form.put(update.url(church.id), options);
        } else {
            form.post(store.url(), options);
        }
    };

    return (
        <Dialog open={open} onOpenChange={(value) => !value && onClose()}>
            <DialogContent>
                <form onSubmit={submit} className="grid gap-4">
                    <DialogHeader>
                        <DialogTitle>
                            {church ? 'Ubah cabang' : 'Cabang baru'}
                        </DialogTitle>
                        <DialogDescription>
                            Data inti setiap cabang terpisah. Cabang
                            dinonaktifkan, tidak dihapus.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-2">
                        <Label htmlFor="church-name">Nama cabang</Label>
                        <Input
                            id="church-name"
                            value={form.data.name}
                            onChange={(e) =>
                                form.setData('name', e.target.value)
                            }
                            required
                        />
                        <InputError message={form.errors.name} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="church-slug">
                            Alamat singkat{' '}
                            <span className="font-normal text-muted-foreground">
                                (otomatis dari nama bila kosong)
                            </span>
                        </Label>
                        <Input
                            id="church-slug"
                            value={form.data.slug}
                            placeholder="ggp-elim"
                            onChange={(e) =>
                                form.setData('slug', e.target.value)
                            }
                        />
                        <InputError message={form.errors.slug} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="church-address">Alamat</Label>
                        <Input
                            id="church-address"
                            value={form.data.address}
                            onChange={(e) =>
                                form.setData('address', e.target.value)
                            }
                        />
                        <InputError message={form.errors.address} />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="church-whatsapp">WhatsApp</Label>
                            <Input
                                id="church-whatsapp"
                                value={form.data.whatsapp}
                                onChange={(e) =>
                                    form.setData('whatsapp', e.target.value)
                                }
                            />
                            <InputError message={form.errors.whatsapp} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="church-email">Email</Label>
                            <Input
                                id="church-email"
                                type="email"
                                value={form.data.email}
                                onChange={(e) =>
                                    form.setData('email', e.target.value)
                                }
                            />
                            <InputError message={form.errors.email} />
                        </div>
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="church-timezone">Zona waktu</Label>
                        <select
                            id="church-timezone"
                            className={selectClass}
                            value={form.data.timezone}
                            onChange={(e) =>
                                form.setData('timezone', e.target.value)
                            }
                        >
                            {timezones.map((zone) => (
                                <option key={zone.value} value={zone.value}>
                                    {zone.label}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="church-active"
                            checked={form.data.is_active}
                            disabled={church?.isDefault}
                            onCheckedChange={(checked) =>
                                form.setData('is_active', checked === true)
                            }
                        />
                        <Label htmlFor="church-active">Aktif</Label>
                        {church?.isDefault && (
                            <span className="text-xs text-muted-foreground">
                                (cabang utama selalu aktif)
                            </span>
                        )}
                    </div>
                    <InputError message={form.errors.is_active} />
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={onClose}
                        >
                            Batal
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            Simpan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
