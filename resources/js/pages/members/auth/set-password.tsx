import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

type Props = {
    name: string;
    email: string;
    /** Signed URL of this page; the form posts back to it. */
    action: string;
};

/** "Buat password" page opened from the approval email (PRD 4.4). */
export default function SetPassword({ name, email, action }: Props) {
    return (
        <>
            <Head title="Buat password" />
            <Form
                action={action}
                method="post"
                resetOnError={['password', 'password_confirmation']}
                disableWhileProcessing
                className="grid gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <p className="text-sm text-muted-foreground">
                            Shalom, {name}! Buat password untuk akun Anda.
                        </p>

                        <div className="grid gap-2">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                value={email}
                                autoComplete="username"
                                readOnly
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">Password baru</Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                required
                                autoFocus
                                autoComplete="new-password"
                            />
                            <InputError message={errors.password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">
                                Ulangi password
                            </Label>
                            <PasswordInput
                                id="password_confirmation"
                                name="password_confirmation"
                                required
                                autoComplete="new-password"
                            />
                        </div>

                        <Button type="submit" className="w-full">
                            {processing && <Spinner />}
                            Simpan dan masuk
                        </Button>
                    </>
                )}
            </Form>
        </>
    );
}

SetPassword.layout = {
    title: 'Buat password',
    description: 'Pendaftaran Anda sudah disetujui.',
};
