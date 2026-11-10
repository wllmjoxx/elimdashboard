import { useForm } from '@inertiajs/react';
import { UserPlus } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
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
import { invite as inviteRoute } from '@/routes/members';
import type { Option } from './types';

export type InviteOptions = {
    roles: Option[];
    churches: { id: number; name: string }[];
};

const selectClass =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

/**
 * Invite a staff account by email (PRD 4.4). The account is active right
 * away; the person sets a password from the invitation email.
 */
export function InviteDialog({ options }: { options: InviteOptions }) {
    const [open, setOpen] = useState(false);
    const defaultRole =
        options.roles.find((role) => role.value === 'servant')?.value ??
        options.roles[0]?.value ??
        '';

    const form = useForm({
        name: '',
        email: '',
        role: defaultRole,
        church_id: options.churches[0]?.id.toString() ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) =>
            options.churches.length > 0
                ? data
                : { name: data.name, email: data.email, role: data.role },
        );
        form.post(inviteRoute.url(), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setOpen(false);
            },
        });
    };

    return (
        <>
            <Button onClick={() => setOpen(true)}>
                <UserPlus />
                Undang akun staf
            </Button>
            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <form onSubmit={submit} className="grid gap-4">
                        <DialogHeader>
                            <DialogTitle>Undang akun staf</DialogTitle>
                            <DialogDescription>
                                Kami kirim email undangan berisi tautan untuk
                                membuat password. Akun langsung aktif.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="grid gap-2">
                            <Label htmlFor="invite-name">Nama lengkap</Label>
                            <Input
                                id="invite-name"
                                value={form.data.name}
                                onChange={(e) =>
                                    form.setData('name', e.target.value)
                                }
                                required
                            />
                            <InputError message={form.errors.name} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="invite-email">Email</Label>
                            <Input
                                id="invite-email"
                                type="email"
                                value={form.data.email}
                                onChange={(e) =>
                                    form.setData('email', e.target.value)
                                }
                                required
                            />
                            <InputError message={form.errors.email} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="invite-role">Role</Label>
                            <select
                                id="invite-role"
                                className={selectClass}
                                value={form.data.role}
                                onChange={(e) =>
                                    form.setData('role', e.target.value)
                                }
                            >
                                {options.roles.map((role) => (
                                    <option key={role.value} value={role.value}>
                                        {role.label}
                                    </option>
                                ))}
                            </select>
                            <InputError message={form.errors.role} />
                        </div>
                        {options.churches.length > 0 && (
                            <div className="grid gap-2">
                                <Label htmlFor="invite-church">Cabang</Label>
                                <select
                                    id="invite-church"
                                    className={selectClass}
                                    value={form.data.church_id}
                                    onChange={(e) =>
                                        form.setData(
                                            'church_id',
                                            e.target.value,
                                        )
                                    }
                                >
                                    {options.churches.map((church) => (
                                        <option
                                            key={church.id}
                                            value={church.id}
                                        >
                                            {church.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={form.errors.church_id} />
                            </div>
                        )}
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setOpen(false)}
                            >
                                Batal
                            </Button>
                            <Button type="submit" disabled={form.processing}>
                                Kirim undangan
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}
