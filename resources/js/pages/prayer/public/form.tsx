import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { CheckCircle2, HandHeart, Lock } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { Glass } from '@/components/glass';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Turnstile } from '@/modules/core/turnstile';
import { Verse } from '@/modules/landing/components';
import { nav } from '@/modules/landing/nav';
import { Reveal } from '@/modules/landing/reveal';
import { SiteFooter } from '@/modules/landing/site-footer';
import type { Option } from '@/modules/prayer/types';
import { login } from '@/routes';
import { history, store } from '@/routes/prayer';

type Props = {
    intro: { title: string; text: string; verse: string; reference: string };
    categories: Option[];
    prefill: {
        full_name: string | null;
        phone: string | null;
        address: string | null;
        age: number | null;
    } | null;
    remaining: number | null;
    captchaSiteKey: string | null;
    confirmation: string | null;
};

const fieldClass =
    'w-full rounded-md border border-input bg-background/70 px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

/** Public Form Doa (PRD 4.6) for guests and signed-in members. */
export default function PrayerForm({
    intro,
    categories,
    prefill,
    remaining,
    captchaSiteKey,
    confirmation,
}: Props) {
    const { auth } = usePage().props;
    const [captchaReset, setCaptchaReset] = useState(0);
    const [sentAgain, setSentAgain] = useState(false);

    const form = useForm({
        full_name: prefill?.full_name ?? '',
        age: prefill?.age?.toString() ?? '',
        phone: prefill?.phone ?? '',
        address: prefill?.address ?? '',
        category: '',
        body: '',
        website: '',
        captcha_token: '',
    });

    const limitReached = remaining !== null && remaining <= 0;
    const showConfirmation = confirmation && !sentAgain;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(store.url(), {
            preserveScroll: true,
            onError: () => {
                form.setData('captcha_token', '');
                setCaptchaReset((value) => value + 1);
            },
        });
    };

    return (
        <>
            <Head title="Form Doa">
                <meta
                    name="description"
                    content="Kirimkan pokok doa Anda. Tim Doa GGP Elim Ciputat akan mendoakannya setiap minggu."
                />
            </Head>

            <section className="px-4 pt-14 pb-16 sm:px-6 sm:pt-20">
                <div className="mx-auto grid max-w-6xl gap-10 lg:grid-cols-5">
                    <Reveal className="lg:col-span-2">
                        <p className="mb-3 flex items-center gap-2 text-sm font-semibold tracking-widest text-gold uppercase">
                            <HandHeart className="size-4" />
                            Form Doa
                        </p>
                        <h1 className="font-serif text-4xl leading-tight font-semibold tracking-tight text-balance sm:text-5xl">
                            {intro.title}
                        </h1>
                        <p className="mt-4 text-lg text-pretty text-muted-foreground">
                            {intro.text}
                        </p>
                        <Verse
                            text={intro.verse}
                            reference={intro.reference}
                            className="mt-8"
                        />
                        <p className="mt-8 flex gap-2 text-sm text-muted-foreground">
                            <Lock className="mt-0.5 size-4 shrink-0" />
                            Data dan isi doa Anda disimpan terenkripsi dan hanya
                            dibaca oleh Tim Doa dan Admin GGP Elim.
                        </p>
                    </Reveal>

                    <Reveal className="lg:col-span-3">
                        <Glass className="p-6 sm:p-8">
                            {showConfirmation ? (
                                <div
                                    role="status"
                                    className="flex flex-col items-start gap-4 py-6"
                                >
                                    <CheckCircle2 className="size-10 text-green-600" />
                                    <h2 className="font-serif text-2xl font-semibold">
                                        Pokok doa terkirim
                                    </h2>
                                    <p className="text-muted-foreground">
                                        {confirmation}
                                    </p>
                                    <Button
                                        variant="glass"
                                        className="rounded-full"
                                        onClick={() => setSentAgain(true)}
                                    >
                                        Kirim pokok doa lain
                                    </Button>
                                </div>
                            ) : limitReached ? (
                                <div className="space-y-4 py-6">
                                    <h2 className="font-serif text-2xl font-semibold">
                                        Batas minggu ini tercapai
                                    </h2>
                                    <p className="text-muted-foreground">
                                        Anda sudah mengirim 3 pokok doa minggu
                                        ini. Silakan kirim lagi mulai Senin.
                                    </p>
                                    <Button
                                        asChild
                                        variant="glass"
                                        className="rounded-full"
                                    >
                                        <Link href={history()}>
                                            Lihat riwayat doa
                                        </Link>
                                    </Button>
                                </div>
                            ) : (
                                <form
                                    onSubmit={submit}
                                    className="grid gap-5 sm:grid-cols-2"
                                >
                                    {auth.user ? (
                                        <p className="text-sm text-muted-foreground sm:col-span-2">
                                            Data diisi dari profil Anda. Sisa
                                            kiriman minggu ini: {remaining}.{' '}
                                            <Link
                                                href={history()}
                                                className="underline underline-offset-4"
                                            >
                                                Riwayat doa
                                            </Link>
                                        </p>
                                    ) : (
                                        <p className="text-sm text-muted-foreground sm:col-span-2">
                                            Jemaat terdaftar dapat{' '}
                                            <Link
                                                href={login()}
                                                className="underline underline-offset-4"
                                            >
                                                masuk
                                            </Link>{' '}
                                            agar data terisi otomatis dan
                                            riwayat doa tersimpan.
                                        </p>
                                    )}

                                    <div className="grid gap-2 sm:col-span-2">
                                        <Label htmlFor="full_name">
                                            Nama lengkap
                                        </Label>
                                        <Input
                                            id="full_name"
                                            value={form.data.full_name}
                                            onChange={(e) =>
                                                form.setData(
                                                    'full_name',
                                                    e.target.value,
                                                )
                                            }
                                            required
                                            autoComplete="name"
                                            className="bg-background/70"
                                        />
                                        <InputError
                                            message={form.errors.full_name}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="age">
                                            Usia{' '}
                                            <span className="font-normal text-muted-foreground">
                                                (opsional)
                                            </span>
                                        </Label>
                                        <Input
                                            id="age"
                                            type="number"
                                            min={1}
                                            max={120}
                                            value={form.data.age}
                                            onChange={(e) =>
                                                form.setData(
                                                    'age',
                                                    e.target.value,
                                                )
                                            }
                                            className="bg-background/70"
                                        />
                                        <InputError message={form.errors.age} />
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
                                            type="tel"
                                            value={form.data.phone}
                                            onChange={(e) =>
                                                form.setData(
                                                    'phone',
                                                    e.target.value,
                                                )
                                            }
                                            autoComplete="tel"
                                            className="bg-background/70"
                                        />
                                        <InputError
                                            message={form.errors.phone}
                                        />
                                    </div>
                                    <div className="grid gap-2 sm:col-span-2">
                                        <Label htmlFor="address">
                                            Alamat{' '}
                                            <span className="font-normal text-muted-foreground">
                                                (opsional)
                                            </span>
                                        </Label>
                                        <Input
                                            id="address"
                                            value={form.data.address}
                                            onChange={(e) =>
                                                form.setData(
                                                    'address',
                                                    e.target.value,
                                                )
                                            }
                                            autoComplete="street-address"
                                            className="bg-background/70"
                                        />
                                        <InputError
                                            message={form.errors.address}
                                        />
                                    </div>
                                    <div className="grid gap-2 sm:col-span-2">
                                        <Label htmlFor="category">
                                            Kategori
                                        </Label>
                                        <select
                                            id="category"
                                            value={form.data.category}
                                            onChange={(e) =>
                                                form.setData(
                                                    'category',
                                                    e.target.value,
                                                )
                                            }
                                            required
                                            className={fieldClass}
                                        >
                                            <option value="">
                                                Pilih kategori
                                            </option>
                                            {categories.map((category) => (
                                                <option
                                                    key={category.value}
                                                    value={category.value}
                                                >
                                                    {category.label}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError
                                            message={form.errors.category}
                                        />
                                    </div>
                                    <div className="grid gap-2 sm:col-span-2">
                                        <Label htmlFor="body">Pokok doa</Label>
                                        <textarea
                                            id="body"
                                            value={form.data.body}
                                            onChange={(e) =>
                                                form.setData(
                                                    'body',
                                                    e.target.value,
                                                )
                                            }
                                            required
                                            rows={5}
                                            maxLength={3000}
                                            className={fieldClass}
                                        />
                                        <InputError
                                            message={form.errors.body}
                                        />
                                    </div>

                                    {/* Honeypot: hidden from people, filled by bots. */}
                                    <div
                                        aria-hidden="true"
                                        className="absolute -left-[10000px] h-px w-px overflow-hidden"
                                    >
                                        <label htmlFor="website">Website</label>
                                        <input
                                            id="website"
                                            tabIndex={-1}
                                            autoComplete="off"
                                            value={form.data.website}
                                            onChange={(e) =>
                                                form.setData(
                                                    'website',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </div>

                                    {!auth.user && (
                                        <div className="sm:col-span-2">
                                            <Turnstile
                                                siteKey={captchaSiteKey}
                                                resetKey={captchaReset}
                                                onToken={(token) =>
                                                    form.setData(
                                                        'captcha_token',
                                                        token,
                                                    )
                                                }
                                            />
                                            <InputError
                                                message={
                                                    form.errors.captcha_token
                                                }
                                            />
                                        </div>
                                    )}

                                    <div className="sm:col-span-2">
                                        <Button
                                            type="submit"
                                            size="lg"
                                            className="rounded-full"
                                            disabled={
                                                form.processing ||
                                                (captchaSiteKey !== null &&
                                                    !form.data.captcha_token)
                                            }
                                        >
                                            <HandHeart />
                                            {form.processing
                                                ? 'Mengirim…'
                                                : 'Kirim pokok doa'}
                                        </Button>
                                    </div>
                                </form>
                            )}
                        </Glass>
                    </Reveal>
                </div>
            </section>

            <SiteFooter />
        </>
    );
}

PrayerForm.layout = { nav };
