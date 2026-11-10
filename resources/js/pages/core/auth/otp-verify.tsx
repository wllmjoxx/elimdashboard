import { Form, Head } from '@inertiajs/react';
import { REGEXP_ONLY_DIGITS } from 'input-otp';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { create, store, update } from '@/routes/core/otp';

type Props = {
    email: string;
    minutes: number;
    status: string | null;
};

/** Step 2 of "Masuk dengan kode email": type the code (PRD 4.4). */
export default function OtpVerify({ email, minutes, status }: Props) {
    return (
        <>
            <Head title="Masukkan kode" />

            {status && (
                <p
                    role="status"
                    className="mb-4 text-center text-sm font-medium text-green-600"
                >
                    {status}
                </p>
            )}

            <Form
                {...update.form()}
                resetOnError={['code']}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <input type="hidden" name="email" value={email} />
                        <div className="grid justify-items-center gap-3">
                            <Label htmlFor="code">Kode untuk {email}</Label>
                            <InputOTP
                                id="code"
                                name="code"
                                maxLength={6}
                                pattern={REGEXP_ONLY_DIGITS}
                                autoFocus
                                autoComplete="one-time-code"
                                inputMode="numeric"
                            >
                                <InputOTPGroup>
                                    {Array.from({ length: 6 }, (_, index) => (
                                        <InputOTPSlot
                                            key={index}
                                            index={index}
                                        />
                                    ))}
                                </InputOTPGroup>
                            </InputOTP>
                            <InputError message={errors.code ?? errors.email} />
                        </div>

                        <Button type="submit" className="w-full">
                            {processing && <Spinner />}
                            Masuk
                        </Button>
                    </>
                )}
            </Form>

            <div className="mt-6 flex flex-col items-center gap-2 text-sm text-muted-foreground">
                <span>Kode berlaku {minutes} menit.</span>
                <Form {...store.form()} disableWhileProcessing>
                    {({ processing }) => (
                        <>
                            <input type="hidden" name="email" value={email} />
                            <button
                                type="submit"
                                className="underline underline-offset-4 hover:text-foreground disabled:opacity-50"
                                disabled={processing}
                            >
                                Kirim ulang kode
                            </button>
                        </>
                    )}
                </Form>
                <TextLink href={create()}>Ganti email</TextLink>
            </div>
        </>
    );
}

OtpVerify.layout = {
    title: 'Masukkan kode',
    description: 'Cek email Anda, lalu ketik 6 angka kode masuk.',
};
