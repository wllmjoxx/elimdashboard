import { Head, useForm } from '@inertiajs/react';
import { CheckCircle2, EyeOff, Quote } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Turnstile } from '@/modules/core/turnstile';
import { PageHero, Pagination, Panel } from '@/modules/landing/components';
import { nav } from '@/modules/landing/nav';
import { Reveal } from '@/modules/landing/reveal';
import { PrivacyNote } from '@/modules/landing/privacy-note';
import { SiteFooter } from '@/modules/landing/site-footer';
import type { TestimonyItem } from '@/modules/landing/types';
import { store } from '@/routes/testimonies/public';

type Props = {
    testimonies: {
        data: TestimonyItem[];
        links: { url: string | null; label: string; active: boolean }[];
        last_page: number;
    };
    categories: { value: string; label: string }[];
    captchaSiteKey: string | null;
    confirmation: string | null;
};

const fieldClass =
    'w-full rounded-md border border-input bg-background/70 px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

function TestimonyCard({ item }: { item: TestimonyItem }) {
    const [open, setOpen] = useState(false);
    const long = item.body.length > item.excerpt.length;

    return (
        <article className="flex h-full flex-col bg-background p-6 sm:p-8">
            <p className="font-condensed text-xs tracking-[0.24em] text-flame-strong uppercase">
                {item.category}
            </p>
            <h3 className="mt-3 font-display text-2xl leading-tight font-extrabold tracking-tight">
                {item.title}
            </h3>
            <p className="mt-3 flex-1 whitespace-pre-line text-muted-foreground">
                {open ? item.body : item.excerpt}
            </p>
            {long && (
                <button
                    type="button"
                    onClick={() => setOpen((value) => !value)}
                    className="mt-2 self-start text-sm font-semibold text-flame-strong"
                    aria-expanded={open}
                >
                    {open ? 'Tutup' : 'Baca selengkapnya'}
                </button>
            )}
            <p className="mt-6 text-sm text-muted-foreground">— {item.name}</p>
        </article>
    );
}

/** "Kesaksian": approved testimonies and the anonymous form (PRD 4.7). */
export default function TestimoniesPublic({
    testimonies,
    categories,
    captchaSiteKey,
    confirmation,
}: Props) {
    const [captchaReset, setCaptchaReset] = useState(0);
    const [writeAgain, setWriteAgain] = useState(false);

    const form = useForm({
        title: '',
        body: '',
        category: '',
        display_name: '',
        website: '',
        captcha_token: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(store.url(), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setWriteAgain(false);
            },
            onFinish: () => {
                form.setData('captcha_token', '');
                setCaptchaReset((value) => value + 1);
            },
        });
    };

    return (
        <>
            <Head title="Kesaksian" />

            <PageHero
                eyebrow="Kesaksian Jemaat"
                title="Karya Tuhan nyata"
                description="Cerita karya Tuhan dalam hidup jemaat. Setiap kesaksian dibagikan tanpa identitas pengirim."
            >
                <a
                    href="#bagikan"
                    className="mt-8 inline-flex items-center gap-2 bg-white px-5 py-3 text-[13px] font-semibold tracking-[0.12em] text-[#0a0a0d] uppercase transition-colors hover:bg-white/85"
                >
                    Bagikan kesaksian
                </a>
            </PageHero>

            <section className="px-4 py-16 sm:px-6 sm:py-24 lg:px-10">
                <div className="mx-auto max-w-7xl">
                    {testimonies.data.length === 0 ? (
                        <p className="text-lg text-muted-foreground">
                            Belum ada kesaksian yang tampil.
                        </p>
                    ) : (
                        <ul className="grid gap-px border border-foreground/10 bg-foreground/10 sm:grid-cols-2 lg:grid-cols-3">
                            {testimonies.data.map((item) => (
                                <li key={item.id} className="bg-background">
                                    <Reveal className="h-full">
                                        <TestimonyCard item={item} />
                                    </Reveal>
                                </li>
                            ))}
                        </ul>
                    )}

                    {testimonies.last_page > 1 && (
                        <Pagination
                            label="Halaman kesaksian"
                            links={testimonies.links}
                        />
                    )}
                </div>
            </section>

            <section
                id="bagikan"
                className="scroll-mt-24 border-t border-foreground/10 px-4 py-16 sm:px-6 sm:py-24 lg:px-10"
            >
                <Reveal className="mx-auto grid max-w-7xl gap-12 lg:grid-cols-5">
                    <div className="lg:col-span-2">
                        <p className="font-condensed text-sm tracking-[0.3em] text-muted-foreground uppercase">
                            Tulis kesaksian
                        </p>
                        <h2 className="mt-3 font-display text-4xl leading-[0.95] font-black tracking-tight sm:text-5xl">
                            Bagikan kesaksian Anda
                        </h2>
                        <p className="mt-4 text-muted-foreground">
                            Ceritakan apa yang Tuhan kerjakan. Kesaksian tampil
                            setelah ditinjau Admin.
                        </p>
                        <p className="mt-6 flex gap-2 text-sm text-muted-foreground">
                            <EyeOff className="mt-0.5 size-4 shrink-0" />
                            Kami tidak menyimpan nama akun, email, atau alamat
                            IP Anda. Admin pun tidak dapat mengetahui
                            pengirimnya, dan kesaksian tidak bisa diubah atau
                            dihapus setelah dikirim.
                        </p>
                    </div>

                    <Panel className="p-6 sm:p-10 lg:col-span-3">
                        {confirmation && !writeAgain ? (
                            <div
                                role="status"
                                className="flex flex-col items-start gap-4 py-6"
                            >
                                <CheckCircle2 className="size-10 text-green-600" />
                                <h3 className="font-display text-3xl font-extrabold tracking-tight">
                                    Kesaksian terkirim
                                </h3>
                                <p className="text-muted-foreground">
                                    {confirmation}
                                </p>
                                <Button
                                    variant="outline"
                                    className="rounded-none tracking-[0.12em] uppercase"
                                    onClick={() => setWriteAgain(true)}
                                >
                                    Tulis kesaksian lain
                                </Button>
                            </div>
                        ) : (
                            <form onSubmit={submit} className="grid gap-5">
                                <div className="grid gap-2">
                                    <Label htmlFor="title">Judul</Label>
                                    <Input
                                        id="title"
                                        value={form.data.title}
                                        onChange={(e) =>
                                            form.setData(
                                                'title',
                                                e.target.value,
                                            )
                                        }
                                        maxLength={150}
                                        required
                                        className="bg-background/70"
                                    />
                                    <InputError message={form.errors.title} />
                                </div>
                                <div className="grid gap-5 sm:grid-cols-2">
                                    <div className="grid gap-2">
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
                                    <div className="grid gap-2">
                                        <Label htmlFor="display_name">
                                            Nama tampilan{' '}
                                            <span className="font-normal text-muted-foreground">
                                                (opsional)
                                            </span>
                                        </Label>
                                        <Input
                                            id="display_name"
                                            value={form.data.display_name}
                                            onChange={(e) =>
                                                form.setData(
                                                    'display_name',
                                                    e.target.value,
                                                )
                                            }
                                            maxLength={60}
                                            placeholder="Kosongkan agar tampil sebagai Anonim"
                                            className="bg-background/70"
                                        />
                                        <InputError
                                            message={form.errors.display_name}
                                        />
                                    </div>
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="body">Kesaksian</Label>
                                    <textarea
                                        id="body"
                                        value={form.data.body}
                                        onChange={(e) =>
                                            form.setData('body', e.target.value)
                                        }
                                        rows={7}
                                        maxLength={5000}
                                        required
                                        className={fieldClass}
                                    />
                                    <InputError message={form.errors.body} />
                                </div>

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

                                <div>
                                    <Turnstile
                                        siteKey={captchaSiteKey}
                                        resetKey={captchaReset}
                                        onToken={(token) =>
                                            form.setData('captcha_token', token)
                                        }
                                    />
                                    <InputError
                                        message={form.errors.captcha_token}
                                    />
                                </div>

                                <div>
                                    <Button
                                        type="submit"
                                        size="lg"
                                        className="rounded-none tracking-[0.12em] uppercase"
                                        disabled={
                                            form.processing ||
                                            (captchaSiteKey !== null &&
                                                !form.data.captcha_token)
                                        }
                                    >
                                        <Quote />
                                        {form.processing
                                            ? 'Mengirim…'
                                            : 'Kirim kesaksian'}
                                    </Button>
                                    <PrivacyNote
                                        action="mengirim kesaksian"
                                        className="mt-3"
                                    />
                                </div>
                            </form>
                        )}
                    </Panel>
                </Reveal>
            </section>

            <SiteFooter />
        </>
    );
}

TestimoniesPublic.layout = { nav };
