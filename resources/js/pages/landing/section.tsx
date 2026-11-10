import { Head, Link, useForm } from '@inertiajs/react';
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
type ContentValue =
    | string
    | null
    | undefined
    | Slide[]
    | { label: string; href: string }
    | string[]
    | Milestone[];
type Content = Record<string, ContentValue>;

type Props = {
    section: {
        key: string;
        label: string;
        place: string | null;
        content: Content;
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
        ...(section.key === 'about'
            ? {
                  paragraphs: (content.paragraphs as string[] | undefined) ?? [
                      '',
                  ],
                  milestones:
                      (content.milestones as Milestone[] | undefined) ?? [],
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

                <form onSubmit={submit} className="grid gap-6">
                    {section.key === 'hero' && (
                        <>
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

LandingSectionEditor.layout = {
    breadcrumbs: [{ title: 'Landing Page', href: '/admin/landing' }],
};
