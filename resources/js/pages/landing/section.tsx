import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowDown, ArrowLeft, ArrowUp, Plus, Trash2 } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { LandingAdminNav } from '@/modules/landing/admin-nav';
import { index } from '@/routes/landing';
import { index as mediaPage } from '@/routes/landing/media';
import { photo as pastorPhoto } from '@/routes/landing/pastor';
import { update } from '@/routes/landing/sections';

type Slide = {
    eyebrow?: string;
    title: string;
    text?: string;
    verse?: string;
    reference?: string;
};
type Cta = { label: string; href: string } | null;
type Milestone = { year: string; text: string };
type Point = { heading: string; body: string };
type ContentValue =
    | string
    | null
    | undefined
    | Slide[]
    | { label: string; href: string }
    | string[]
    | Milestone[]
    | Point[];
type Content = Record<string, ContentValue>;

type Props = {
    section: {
        key: string;
        label: string;
        place: string | null;
        content: Content;
        photoUrl?: string | null;
    };
    flash: string | null;
};

type FieldSpec = {
    name: string;
    label: string;
    multiline?: boolean;
    optional?: boolean;
    placeholder?: string;
};

/** Plain text sections: their fields in display order. */
const simpleFields: Record<string, FieldSpec[]> = {
    mission: [
        { name: 'title', label: 'Judul kecil', placeholder: 'Misi' },
        { name: 'text', label: 'Pernyataan misi', multiline: true },
        { name: 'verse', label: 'Ayat', multiline: true, optional: true },
        {
            name: 'reference',
            label: 'Rujukan ayat',
            optional: true,
            placeholder: 'Lukas 19:10',
        },
    ],
    devotional: [
        {
            name: 'label',
            label: 'Label di sorotan Home',
            placeholder: 'Renungan Harian',
        },
    ],
    schedule: [
        { name: 'title', label: 'Judul', placeholder: 'Jadwal Kegiatan' },
        {
            name: 'subtitle',
            label: 'Keterangan',
            optional: true,
            placeholder: 'Semua waktu dalam WIB.',
        },
    ],
    prayer: [
        { name: 'title', label: 'Judul', placeholder: 'Butuh didoakan?' },
        { name: 'text', label: 'Teks pengantar', multiline: true },
        { name: 'verse', label: 'Ayat', multiline: true, optional: true },
        {
            name: 'reference',
            label: 'Rujukan ayat',
            optional: true,
            placeholder: 'Matius 21:22',
        },
    ],
    gallery: [{ name: 'title', label: 'Judul', placeholder: 'Galeri' }],
    events: [
        { name: 'title', label: 'Judul', placeholder: 'Kegiatan' },
        {
            name: 'subtitle',
            label: 'Keterangan',
            optional: true,
            placeholder: 'Yang sedang berjalan dan akan datang di GGP Elim.',
        },
    ],
    testimonies: [
        { name: 'title', label: 'Judul', placeholder: 'Kesaksian' },
        { name: 'text', label: 'Teks pengantar', optional: true },
    ],
    contact: [
        { name: 'title', label: 'Judul', placeholder: 'Alamat & Kontak' },
        { name: 'address', label: 'Alamat', multiline: true },
        {
            name: 'whatsapp',
            label: 'Nomor WhatsApp',
            optional: true,
            placeholder: '0858 8385 8048',
        },
        {
            name: 'hours',
            label: 'Jam operasional',
            optional: true,
            placeholder: 'Senin–Sabtu, 10.00–19.00',
        },
        { name: 'maps_query', label: 'Kata kunci Google Maps', optional: true },
    ],
};

const textareaClass =
    'w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

function Field({
    id,
    label,
    value,
    onChange,
    error,
    multiline = false,
    optional = false,
    placeholder,
}: {
    id: string;
    label: string;
    value: string;
    onChange: (value: string) => void;
    error?: string;
    multiline?: boolean;
    optional?: boolean;
    placeholder?: string;
}) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>
                {label}{' '}
                {optional && (
                    <span className="font-normal text-muted-foreground">
                        (opsional)
                    </span>
                )}
            </Label>
            {multiline ? (
                <textarea
                    id={id}
                    rows={3}
                    value={value}
                    placeholder={placeholder}
                    onChange={(e) => onChange(e.target.value)}
                    className={textareaClass}
                />
            ) : (
                <Input
                    id={id}
                    value={value}
                    placeholder={placeholder}
                    onChange={(e) => onChange(e.target.value)}
                />
            )}
            <InputError message={error} />
        </div>
    );
}

function Repeater({
    title,
    children,
    onAdd,
    addLabel,
    canAdd,
}: {
    title: string;
    children: ReactNode;
    onAdd: () => void;
    addLabel: string;
    canAdd: boolean;
}) {
    return (
        <Card>
            <CardHeader className="flex-row items-center justify-between">
                <CardTitle>{title}</CardTitle>
                <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    onClick={onAdd}
                    disabled={!canAdd}
                >
                    <Plus />
                    {addLabel}
                </Button>
            </CardHeader>
            <CardContent className="grid gap-4">{children}</CardContent>
        </Card>
    );
}

function ItemTools({
    index,
    total,
    onMove,
    onRemove,
    label,
}: {
    index: number;
    total: number;
    onMove: (step: number) => void;
    onRemove: () => void;
    label: string;
}) {
    return (
        <div className="flex items-center gap-1">
            <Button
                type="button"
                size="icon"
                variant="ghost"
                disabled={index === 0}
                onClick={() => onMove(-1)}
                aria-label={`Naikkan ${label}`}
            >
                <ArrowUp />
            </Button>
            <Button
                type="button"
                size="icon"
                variant="ghost"
                disabled={index === total - 1}
                onClick={() => onMove(1)}
                aria-label={`Turunkan ${label}`}
            >
                <ArrowDown />
            </Button>
            <Button
                type="button"
                size="icon"
                variant="ghost"
                disabled={total === 1}
                onClick={onRemove}
                aria-label={`Hapus ${label}`}
            >
                <Trash2 />
            </Button>
        </div>
    );
}

function moveItem<T>(list: T[], index: number, step: number): T[] {
    const next = [...list];
    const target = index + step;

    if (target < 0 || target >= next.length) {
        return next;
    }

    [next[index], next[target]] = [next[target], next[index]];

    return next;
}

/** Text editor for one landing section (PRD 4.3, 4.8). */
export default function LandingSectionEditor({ section, flash }: Props) {
    const content = section.content;
    const form = useForm<Content>({
        ...content,
        ...(section.key === 'hero'
            ? {
                  slides: (content.slides as Slide[] | undefined) ?? [
                      { title: '' },
                  ],
                  primary_cta: (content.primary_cta as Cta) ?? {
                      label: '',
                      href: '',
                  },
                  secondary_cta: (content.secondary_cta as Cta) ?? {
                      label: '',
                      href: '',
                  },
              }
            : {}),
        ...(section.key === 'pastor'
            ? {
                  paragraphs: (content.paragraphs as string[] | undefined) ?? [
                      '',
                  ],
              }
            : {}),
        ...(section.key === 'about'
            ? {
                  paragraphs: (content.paragraphs as string[] | undefined) ?? [
                      '',
                  ],
                  milestones:
                      (content.milestones as Milestone[] | undefined) ?? [],
              }
            : {}),
        ...(section.key === 'privacy'
            ? {
                  points: (content.points as Point[] | undefined) ?? [
                      { heading: '', body: '' },
                  ],
              }
            : {}),
    });

    const errors = form.errors as Record<string, string | undefined>;
    const text = (name: string) =>
        (form.data[name] as string | null | undefined) ?? '';

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(update.url(section.key), { preserveScroll: true });
    };

    const slides = (form.data.slides as Slide[] | undefined) ?? [];
    const setSlide = (index: number, patch: Partial<Slide>) =>
        form.setData(
            'slides',
            slides.map((slide, position) =>
                position === index ? { ...slide, ...patch } : slide,
            ),
        );

    const paragraphs = (form.data.paragraphs as string[] | undefined) ?? [];
    const milestones = (form.data.milestones as Milestone[] | undefined) ?? [];
    const points = (form.data.points as Point[] | undefined) ?? [];
    const setPoint = (index: number, patch: Partial<Point>) =>
        form.setData(
            'points',
            points.map((point, position) =>
                position === index ? { ...point, ...patch } : point,
            ),
        );

    const cta = (name: 'primary_cta' | 'secondary_cta', title: string) => {
        const value = (form.data[name] as Cta) ?? { label: '', href: '' };

        return (
            <div className="grid gap-4 sm:grid-cols-2">
                <Field
                    id={`${name}-label`}
                    label={`${title}: label`}
                    optional
                    value={value.label}
                    onChange={(label) =>
                        form.setData(name, { ...value, label })
                    }
                    error={errors[`${name}.label`]}
                />
                <Field
                    id={`${name}-href`}
                    label={`${title}: tautan`}
                    optional
                    placeholder="/jadwal"
                    value={value.href}
                    onChange={(href) => form.setData(name, { ...value, href })}
                    error={errors[`${name}.href`]}
                />
            </div>
        );
    };

    return (
        <>
            <Head title={section.label} />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <LandingAdminNav />
                <div>
                    <Link
                        href={index.url()}
                        className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" />
                        Tata letak
                    </Link>
                    <Heading
                        title={section.label}
                        description={
                            section.place
                                ? `Tampil di: ${section.place}`
                                : undefined
                        }
                    />
                </div>

                {flash && (
                    <p
                        role="status"
                        className="rounded-xl border border-green-600/30 bg-green-600/10 px-4 py-3 text-sm"
                    >
                        {flash}
                    </p>
                )}

                {/* Its own form: forms cannot be nested in the text form. */}
                {section.key === 'pastor' && (
                    <PastorPhoto url={section.photoUrl ?? null} />
                )}

                <form onSubmit={submit} className="grid gap-6">
                    {section.key === 'hero' && (
                        <>
                            <Card>
                                <CardContent className="grid gap-4 pt-6">
                                    <Field
                                        id="headline"
                                        label="Judul besar"
                                        optional
                                        placeholder="Selamat Datang"
                                        value={text('headline')}
                                        onChange={(value) =>
                                            form.setData('headline', value)
                                        }
                                        error={errors.headline}
                                    />
                                    <Field
                                        id="subheadline"
                                        label="Subjudul"
                                        optional
                                        placeholder="di GGP ELIM Rumah Kasih Karunia"
                                        value={text('subheadline')}
                                        onChange={(value) =>
                                            form.setData('subheadline', value)
                                        }
                                        error={errors.subheadline}
                                    />
                                    <div className="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-muted px-4 py-3 text-sm">
                                        <span>
                                            Video beranda dan live streaming
                                            diatur di halaman Video &amp; Live.
                                            Slide di bawah tampil sebagai tema
                                            di atas video.
                                        </span>
                                        <Button
                                            asChild
                                            size="sm"
                                            variant="outline"
                                        >
                                            <Link href={mediaPage()}>
                                                Unggah video / atur live
                                            </Link>
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                            <Repeater
                                title="Slide"
                                addLabel="Tambah slide"
                                canAdd={slides.length < 5}
                                onAdd={() =>
                                    form.setData('slides', [
                                        ...slides,
                                        { title: '' },
                                    ])
                                }
                            >
                                <InputError message={errors.slides} />
                                {slides.map((slide, position) => (
                                    <fieldset
                                        key={position}
                                        className="grid gap-4 rounded-xl border p-4"
                                    >
                                        <div className="flex items-center justify-between">
                                            <legend className="text-sm font-medium">
                                                Slide {position + 1}
                                            </legend>
                                            <ItemTools
                                                index={position}
                                                total={slides.length}
                                                label={`slide ${position + 1}`}
                                                onMove={(step) =>
                                                    form.setData(
                                                        'slides',
                                                        moveItem(
                                                            slides,
                                                            position,
                                                            step,
                                                        ),
                                                    )
                                                }
                                                onRemove={() =>
                                                    form.setData(
                                                        'slides',
                                                        slides.filter(
                                                            (_, i) =>
                                                                i !== position,
                                                        ),
                                                    )
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-4 sm:grid-cols-[12rem_1fr]">
                                            <Field
                                                id={`slide-${position}-eyebrow`}
                                                label="Label kecil"
                                                optional
                                                placeholder="Tema"
                                                value={slide.eyebrow ?? ''}
                                                onChange={(eyebrow) =>
                                                    setSlide(position, {
                                                        eyebrow,
                                                    })
                                                }
                                                error={
                                                    errors[
                                                        `slides.${position}.eyebrow`
                                                    ]
                                                }
                                            />
                                            <Field
                                                id={`slide-${position}-title`}
                                                label="Judul"
                                                value={slide.title}
                                                onChange={(title) =>
                                                    setSlide(position, {
                                                        title,
                                                    })
                                                }
                                                error={
                                                    errors[
                                                        `slides.${position}.title`
                                                    ]
                                                }
                                            />
                                        </div>
                                        <Field
                                            id={`slide-${position}-text`}
                                            label="Teks"
                                            optional
                                            multiline
                                            value={slide.text ?? ''}
                                            onChange={(value) =>
                                                setSlide(position, {
                                                    text: value,
                                                })
                                            }
                                            error={
                                                errors[
                                                    `slides.${position}.text`
                                                ]
                                            }
                                        />
                                        <div className="grid gap-4 sm:grid-cols-[1fr_12rem]">
                                            <Field
                                                id={`slide-${position}-verse`}
                                                label="Ayat"
                                                optional
                                                multiline
                                                value={slide.verse ?? ''}
                                                onChange={(verse) =>
                                                    setSlide(position, {
                                                        verse,
                                                    })
                                                }
                                                error={
                                                    errors[
                                                        `slides.${position}.verse`
                                                    ]
                                                }
                                            />
                                            <Field
                                                id={`slide-${position}-reference`}
                                                label="Rujukan"
                                                optional
                                                placeholder="Ibrani 4:14"
                                                value={slide.reference ?? ''}
                                                onChange={(reference) =>
                                                    setSlide(position, {
                                                        reference,
                                                    })
                                                }
                                                error={
                                                    errors[
                                                        `slides.${position}.reference`
                                                    ]
                                                }
                                            />
                                        </div>
                                    </fieldset>
                                ))}
                            </Repeater>
                            <Card>
                                <CardHeader>
                                    <CardTitle>Tombol</CardTitle>
                                </CardHeader>
                                <CardContent className="grid gap-4">
                                    {cta('primary_cta', 'Tombol utama')}
                                    {cta('secondary_cta', 'Tombol kedua')}
                                    <p className="text-xs text-muted-foreground">
                                        Kosongkan label untuk menyembunyikan
                                        tombol. Tautan diawali "/" untuk halaman
                                        situs ini.
                                    </p>
                                </CardContent>
                            </Card>
                        </>
                    )}

                    {section.key === 'about' && (
                        <>
                            <Card>
                                <CardContent className="grid gap-4 pt-6">
                                    <Field
                                        id="title"
                                        label="Judul"
                                        value={text('title')}
                                        onChange={(value) =>
                                            form.setData('title', value)
                                        }
                                        error={errors.title}
                                    />
                                </CardContent>
                            </Card>
                            <Repeater
                                title="Paragraf sejarah"
                                addLabel="Tambah paragraf"
                                canAdd={paragraphs.length < 8}
                                onAdd={() =>
                                    form.setData('paragraphs', [
                                        ...paragraphs,
                                        '',
                                    ])
                                }
                            >
                                <InputError message={errors.paragraphs} />
                                {paragraphs.map((paragraph, position) => (
                                    <div
                                        key={position}
                                        className="flex items-start gap-2"
                                    >
                                        <div className="flex-1">
                                            <Field
                                                id={`paragraph-${position}`}
                                                label={`Paragraf ${position + 1}`}
                                                multiline
                                                value={paragraph}
                                                onChange={(value) =>
                                                    form.setData(
                                                        'paragraphs',
                                                        paragraphs.map(
                                                            (item, i) =>
                                                                i === position
                                                                    ? value
                                                                    : item,
                                                        ),
                                                    )
                                                }
                                                error={
                                                    errors[
                                                        `paragraphs.${position}`
                                                    ]
                                                }
                                            />
                                        </div>
                                        <div className="pt-6">
                                            <ItemTools
                                                index={position}
                                                total={paragraphs.length}
                                                label={`paragraf ${position + 1}`}
                                                onMove={(step) =>
                                                    form.setData(
                                                        'paragraphs',
                                                        moveItem(
                                                            paragraphs,
                                                            position,
                                                            step,
                                                        ),
                                                    )
                                                }
                                                onRemove={() =>
                                                    form.setData(
                                                        'paragraphs',
                                                        paragraphs.filter(
                                                            (_, i) =>
                                                                i !== position,
                                                        ),
                                                    )
                                                }
                                            />
                                        </div>
                                    </div>
                                ))}
                            </Repeater>
                            <Repeater
                                title="Tonggak sejarah"
                                addLabel="Tambah tonggak"
                                canAdd={milestones.length < 8}
                                onAdd={() =>
                                    form.setData('milestones', [
                                        ...milestones,
                                        { year: '', text: '' },
                                    ])
                                }
                            >
                                {milestones.length === 0 && (
                                    <p className="text-sm text-muted-foreground">
                                        Belum ada tonggak sejarah.
                                    </p>
                                )}
                                {milestones.map((milestone, position) => (
                                    <div
                                        key={position}
                                        className="grid items-start gap-2 sm:grid-cols-[8rem_1fr_auto]"
                                    >
                                        <Field
                                            id={`milestone-${position}-year`}
                                            label="Tahun"
                                            placeholder="1923"
                                            value={milestone.year}
                                            onChange={(year) =>
                                                form.setData(
                                                    'milestones',
                                                    milestones.map((item, i) =>
                                                        i === position
                                                            ? { ...item, year }
                                                            : item,
                                                    ),
                                                )
                                            }
                                            error={
                                                errors[
                                                    `milestones.${position}.year`
                                                ]
                                            }
                                        />
                                        <Field
                                            id={`milestone-${position}-text`}
                                            label="Keterangan"
                                            value={milestone.text}
                                            onChange={(value) =>
                                                form.setData(
                                                    'milestones',
                                                    milestones.map((item, i) =>
                                                        i === position
                                                            ? {
                                                                  ...item,
                                                                  text: value,
                                                              }
                                                            : item,
                                                    ),
                                                )
                                            }
                                            error={
                                                errors[
                                                    `milestones.${position}.text`
                                                ]
                                            }
                                        />
                                        <div className="sm:pt-6">
                                            <Button
                                                type="button"
                                                size="icon"
                                                variant="ghost"
                                                onClick={() =>
                                                    form.setData(
                                                        'milestones',
                                                        milestones.filter(
                                                            (_, i) =>
                                                                i !== position,
                                                        ),
                                                    )
                                                }
                                                aria-label={`Hapus tonggak ${position + 1}`}
                                            >
                                                <Trash2 />
                                            </Button>
                                        </div>
                                    </div>
                                ))}
                            </Repeater>
                        </>
                    )}

                    {section.key === 'pastor' && (
                        <>
                            <Card>
                                <CardContent className="grid gap-4 pt-6 sm:grid-cols-2">
                                    <Field
                                        id="label"
                                        label="Label kecil"
                                        placeholder="Gembala Sidang"
                                        value={text('label')}
                                        onChange={(value) =>
                                            form.setData('label', value)
                                        }
                                        error={errors.label}
                                    />
                                    <Field
                                        id="name"
                                        label="Nama"
                                        placeholder="Pdt. Nama Lengkap"
                                        value={text('name')}
                                        onChange={(value) =>
                                            form.setData('name', value)
                                        }
                                        error={errors.name}
                                    />
                                </CardContent>
                            </Card>
                            <Repeater
                                title="Bio"
                                addLabel="Tambah paragraf"
                                canAdd={paragraphs.length < 4}
                                onAdd={() =>
                                    form.setData('paragraphs', [
                                        ...paragraphs,
                                        '',
                                    ])
                                }
                            >
                                <InputError message={errors.paragraphs} />
                                {paragraphs.map((paragraph, position) => (
                                    <div
                                        key={position}
                                        className="flex items-start gap-2"
                                    >
                                        <div className="flex-1">
                                            <Field
                                                id={`paragraph-${position}`}
                                                label={`Paragraf ${position + 1}`}
                                                multiline
                                                value={paragraph}
                                                onChange={(value) =>
                                                    form.setData(
                                                        'paragraphs',
                                                        paragraphs.map(
                                                            (item, i) =>
                                                                i === position
                                                                    ? value
                                                                    : item,
                                                        ),
                                                    )
                                                }
                                                error={
                                                    errors[
                                                        `paragraphs.${position}`
                                                    ]
                                                }
                                            />
                                        </div>
                                        <div className="pt-6">
                                            <ItemTools
                                                index={position}
                                                total={paragraphs.length}
                                                label={`paragraf ${position + 1}`}
                                                onMove={(step) =>
                                                    form.setData(
                                                        'paragraphs',
                                                        moveItem(
                                                            paragraphs,
                                                            position,
                                                            step,
                                                        ),
                                                    )
                                                }
                                                onRemove={() =>
                                                    form.setData(
                                                        'paragraphs',
                                                        paragraphs.filter(
                                                            (_, i) =>
                                                                i !== position,
                                                        ),
                                                    )
                                                }
                                            />
                                        </div>
                                    </div>
                                ))}
                            </Repeater>
                            <Card>
                                <CardHeader>
                                    <CardTitle>
                                        Tombol dan media sosial
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="grid gap-4 sm:grid-cols-2">
                                    <Field
                                        id="more_label"
                                        label="Label tombol"
                                        optional
                                        placeholder="Lihat selengkapnya"
                                        value={text('more_label')}
                                        onChange={(value) =>
                                            form.setData('more_label', value)
                                        }
                                        error={errors.more_label}
                                    />
                                    <Field
                                        id="more_href"
                                        label="Tautan tombol"
                                        optional
                                        placeholder="/tentang"
                                        value={text('more_href')}
                                        onChange={(value) =>
                                            form.setData('more_href', value)
                                        }
                                        error={errors.more_href}
                                    />
                                    <Field
                                        id="social_name"
                                        label="Nama akun di tombol media sosial"
                                        optional
                                        placeholder="Nama gembala"
                                        value={text('social_name')}
                                        onChange={(value) =>
                                            form.setData('social_name', value)
                                        }
                                        error={errors.social_name}
                                    />
                                    <Field
                                        id="instagram"
                                        label="Tautan Instagram"
                                        optional
                                        placeholder="https://instagram.com/…"
                                        value={text('instagram')}
                                        onChange={(value) =>
                                            form.setData('instagram', value)
                                        }
                                        error={errors.instagram}
                                    />
                                    <Field
                                        id="youtube"
                                        label="Tautan YouTube"
                                        optional
                                        placeholder="https://youtube.com/@…"
                                        value={text('youtube')}
                                        onChange={(value) =>
                                            form.setData('youtube', value)
                                        }
                                        error={errors.youtube}
                                    />
                                    <Field
                                        id="facebook"
                                        label="Tautan Facebook"
                                        optional
                                        placeholder="https://facebook.com/…"
                                        value={text('facebook')}
                                        onChange={(value) =>
                                            form.setData('facebook', value)
                                        }
                                        error={errors.facebook}
                                    />
                                </CardContent>
                            </Card>
                            <p className="text-sm text-muted-foreground">
                                Segmen ini tersembunyi sampai Anda
                                menampilkannya di halaman Tata letak.
                            </p>
                        </>
                    )}

                    {section.key === 'privacy' && (
                        <>
                            <Card>
                                <CardContent className="grid gap-4 pt-6">
                                    <Field
                                        id="title"
                                        label="Judul"
                                        value={text('title')}
                                        onChange={(value) =>
                                            form.setData('title', value)
                                        }
                                        error={errors.title}
                                    />
                                    <Field
                                        id="effective"
                                        label="Berlaku sejak"
                                        optional
                                        placeholder="11 November 2026"
                                        value={text('effective')}
                                        onChange={(value) =>
                                            form.setData('effective', value)
                                        }
                                        error={errors.effective}
                                    />
                                    <Field
                                        id="intro"
                                        label="Pengantar"
                                        optional
                                        multiline
                                        value={text('intro')}
                                        onChange={(value) =>
                                            form.setData('intro', value)
                                        }
                                        error={errors.intro}
                                    />
                                </CardContent>
                            </Card>
                            <Repeater
                                title="Poin kebijakan"
                                addLabel="Tambah poin"
                                canAdd={points.length < 15}
                                onAdd={() =>
                                    form.setData('points', [
                                        ...points,
                                        { heading: '', body: '' },
                                    ])
                                }
                            >
                                <p className="text-sm text-muted-foreground">
                                    Setiap baris pada isi poin tampil sebagai
                                    paragraf terpisah.
                                </p>
                                <InputError message={errors.points} />
                                {points.map((point, position) => (
                                    <div
                                        key={position}
                                        className="grid gap-3 rounded-lg border p-4"
                                    >
                                        <div className="flex items-center justify-between gap-2">
                                            <span className="text-sm font-medium">
                                                Poin {position + 1}
                                            </span>
                                            <ItemTools
                                                index={position}
                                                total={points.length}
                                                label={`poin ${position + 1}`}
                                                onMove={(step) =>
                                                    form.setData(
                                                        'points',
                                                        moveItem(
                                                            points,
                                                            position,
                                                            step,
                                                        ),
                                                    )
                                                }
                                                onRemove={() =>
                                                    form.setData(
                                                        'points',
                                                        points.filter(
                                                            (_, i) =>
                                                                i !== position,
                                                        ),
                                                    )
                                                }
                                            />
                                        </div>
                                        <Field
                                            id={`point-${position}-heading`}
                                            label="Judul poin"
                                            value={point.heading}
                                            onChange={(heading) =>
                                                setPoint(position, { heading })
                                            }
                                            error={
                                                errors[
                                                    `points.${position}.heading`
                                                ]
                                            }
                                        />
                                        <Field
                                            id={`point-${position}-body`}
                                            label="Isi"
                                            multiline
                                            value={point.body}
                                            onChange={(body) =>
                                                setPoint(position, { body })
                                            }
                                            error={
                                                errors[
                                                    `points.${position}.body`
                                                ]
                                            }
                                        />
                                    </div>
                                ))}
                            </Repeater>
                        </>
                    )}

                    {simpleFields[section.key] && (
                        <Card>
                            <CardContent className="grid gap-4 pt-6">
                                {simpleFields[section.key].map((field) => (
                                    <Field
                                        key={field.name}
                                        id={field.name}
                                        label={field.label}
                                        multiline={field.multiline}
                                        optional={field.optional}
                                        placeholder={field.placeholder}
                                        value={text(field.name)}
                                        onChange={(value) =>
                                            form.setData(field.name, value)
                                        }
                                        error={errors[field.name]}
                                    />
                                ))}
                            </CardContent>
                        </Card>
                    )}

                    <div className="flex items-center gap-3">
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? 'Menyimpan…' : 'Simpan'}
                        </Button>
                        {form.recentlySuccessful && (
                            <p className="text-sm text-muted-foreground">
                                Tersimpan.
                            </p>
                        )}
                    </div>
                </form>
            </div>
        </>
    );
}

/** Upload or remove the pastor's photo (saved right away). */
function PastorPhoto({ url }: { url: string | null }) {
    const form = useForm<{ photo: File | null }>({ photo: null });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(pastorPhoto.url(), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>Foto</CardTitle>
            </CardHeader>
            <CardContent className="flex flex-col gap-6 sm:flex-row sm:items-start">
                <div className="flex aspect-[4/5] w-40 shrink-0 items-end justify-center overflow-hidden rounded-xl border bg-muted">
                    {url ? (
                        <img
                            src={url}
                            alt="Foto gembala saat ini"
                            className="h-full w-full object-contain object-bottom"
                        />
                    ) : (
                        <span className="m-auto px-3 text-center text-xs text-muted-foreground">
                            Belum ada foto
                        </span>
                    )}
                </div>
                <form onSubmit={submit} className="grid flex-1 gap-3">
                    <Label htmlFor="pastor-photo">Unggah foto baru</Label>
                    <Input
                        id="pastor-photo"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        onChange={(e) =>
                            form.setData('photo', e.target.files?.[0] ?? null)
                        }
                    />
                    <p className="text-sm text-muted-foreground">
                        Paling bagus foto potret PNG tanpa latar, minimal 600 ×
                        700 px, maksimal 4 MB.
                    </p>
                    <InputError message={form.errors.photo} />
                    <div className="flex flex-wrap gap-2">
                        <Button
                            type="submit"
                            disabled={!form.data.photo || form.processing}
                        >
                            {form.processing ? 'Mengunggah…' : 'Simpan foto'}
                        </Button>
                        {url && (
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() =>
                                    router.delete(pastorPhoto.url(), {
                                        preserveScroll: true,
                                    })
                                }
                            >
                                Hapus foto
                            </Button>
                        )}
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}

LandingSectionEditor.layout = {
    breadcrumbs: [{ title: 'Landing Page', href: '/admin/landing' }],
};
