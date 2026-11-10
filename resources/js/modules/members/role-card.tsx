import { useForm } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { role as roleRoute } from '@/routes/members';
import type { Option } from './types';

export type RoleInfo = {
    current: string | null;
    currentLabel: string | null;
    options: Option[];
};

const selectClass =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

/** An account's role, changeable by those allowed to (PRD 4.2). */
export function RoleCard({
    memberId,
    role,
    canAssign,
}: {
    memberId: number;
    role: RoleInfo;
    canAssign: boolean;
}) {
    const form = useForm({ role: role.current ?? '' });

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <ShieldCheck className="size-4 text-sky" />
                    Role
                </CardTitle>
                <CardDescription>
                    Menentukan menu dan hak akses akun ini. Perubahan dicatat di
                    audit log.
                </CardDescription>
            </CardHeader>
            <CardContent>
                {canAssign ? (
                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            form.post(roleRoute.url(memberId), {
                                preserveScroll: true,
                            });
                        }}
                        className="flex flex-wrap items-end gap-3"
                    >
                        <div className="grid min-w-56 gap-2">
                            <Label htmlFor="role">Role</Label>
                            <select
                                id="role"
                                className={selectClass}
                                value={form.data.role}
                                onChange={(e) =>
                                    form.setData('role', e.target.value)
                                }
                            >
                                {!role.current && (
                                    <option value="">Tanpa role</option>
                                )}
                                {role.options.map((option) => (
                                    <option
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                            <InputError message={form.errors.role} />
                        </div>
                        <Button
                            type="submit"
                            disabled={
                                form.processing ||
                                !form.data.role ||
                                form.data.role === role.current
                            }
                        >
                            Simpan role
                        </Button>
                    </form>
                ) : (
                    <p className="font-medium">
                        {role.currentLabel ?? 'Tanpa role'}
                    </p>
                )}
            </CardContent>
        </Card>
    );
}
