import { Form, Head } from '@inertiajs/react';
import { Mail } from 'lucide-react';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login, register } from '@/routes';
import { store } from '@/routes/core/otp';

/** Step 1 of "Masuk dengan kode email" (PRD 4.4). */
export default function OtpRequest({ minutes }: { minutes: number }) {
    return (
        <>
            <Head title="Masuk dengan kode" />
            <Form
                {...store.form()}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                required
                                autoFocus
                                autoComplete="email"
                                placeholder="email@contoh.com"
                            />
                            <InputError message={errors.email} />
                        </div>

                        <p className="text-sm text-muted-foreground">
                            Kami kirim kode 6 angka ke email Anda. Kode berlaku{' '}
                            {minutes} menit dan hanya bisa dipakai sekali.
                        </p>

                        <Button type="submit" className="w-full">
                            {processing ? <Spinner /> : <Mail />}
                            Kirim kode
                        </Button>

                        <div className="text-center text-sm text-muted-foreground">
                            Ingin memakai password?{' '}
                            <TextLink href={login()}>
                                Masuk dengan password
                            </TextLink>
                        </div>

                        <div className="text-center text-sm text-muted-foreground">
                            Belum punya akun?{' '}
                            <TextLink href={register()}>Daftar</TextLink>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}

OtpRequest.layout = {
    title: 'Masuk dengan kode email',
    description: 'Tanpa password: kami kirim kode masuk ke email Anda.',
};
