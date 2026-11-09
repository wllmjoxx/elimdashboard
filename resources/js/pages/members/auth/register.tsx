import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

type Props = {
    churches: { id: number; name: string }[];
    defaultChurchId: number | null;
};

const selectClass =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

/**
 * Member registration (PRD 4.4). No password here: Admin approves the
 * account first, then the member creates a password from the email.
 */
export default function Register({ churches, defaultChurchId }: Props) {
    return (
        <>
            <Head title="Daftar" />
            <Form
                {...store.form()}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nama lengkap</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    autoFocus
                                    autoComplete="name"
                                    placeholder="Sesuai identitas"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    required
                                    autoComplete="email"
                                    placeholder="email@contoh.com"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="phone">
                                    Nomor HP/WA{' '}
                                    <span className="font-normal text-muted-foreground">
                                        (opsional)
                                    </span>
                                </Label>
                                <Input
                                    id="phone"
                                    name="phone"
                                    type="tel"
                                    autoComplete="tel"
                                    placeholder="0812 3456 7890"
                                />
                                <InputError message={errors.phone} />
                            </div>

                            {churches.length > 1 ? (
                                <div className="grid gap-2">
                                    <Label htmlFor="church_id">Cabang</Label>
                                    <select
                                        id="church_id"
                                        name="church_id"
                                        required
                                        defaultValue={defaultChurchId ?? ''}
                                        className={selectClass}
                                    >
                                        {churches.map((church) => (
                                            <option
                                                key={church.id}
                                                value={church.id}
                                            >
                                                {church.name}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.church_id} />
                                </div>
                            ) : (
                                <>
                                    <input
                                        type="hidden"
                                        name="church_id"
                                        value={
                                            churches[0]?.id ??
                                            defaultChurchId ??
                                            ''
                                        }
                                    />
                                    <InputError message={errors.church_id} />
                                </>
                            )}

                            <p className="text-sm text-muted-foreground">
                                Pendaftaran ditinjau Admin. Setelah disetujui,
                                Anda menerima email untuk membuat password.
                            </p>

                            <Button type="submit" className="w-full">
                                {processing && <Spinner />}
                                Daftar
                            </Button>
                        </div>

                        <div className="text-center text-sm text-muted-foreground">
                            Sudah punya akun?{' '}
                            <TextLink href={login()}>Masuk</TextLink>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}

Register.layout = {
    title: 'Daftar akun jemaat',
    description: 'Isi data Anda. Admin GGP Elim akan meninjau pendaftaran.',
};
