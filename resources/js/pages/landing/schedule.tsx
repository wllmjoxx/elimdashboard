import { Head, router, useForm } from '@inertiajs/react';
import { Eye, EyeOff, Plus, Save, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { LandingAdminNav } from '@/modules/landing/admin-nav';
import { destroy, store, update } from '@/routes/landing/schedule';

type Option = { value: string | number; label: string };

type Schedule = {
    id: number;
    category: string;
    name: string;
    day: number;
    startTime: string;
    location: string | null;
    isActive: boolean;
};

type Props = {
    schedules: Schedule[];
    categories: Option[];
    days: Option[];
    flash: string | null;
};

const selectClass =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

type FormData = {
    category: string;
    name: string;
    day: string;
    start_time: string;
    location: string;
    is_active: boolean;
};

function ScheduleFields({
    data,
    setData,
    errors,
    categories,
    days,
    idPrefix,
}: {
    data: FormData;
    setData: <K extends keyof FormData>(key: K, value: FormData[K]) => void;
    errors: Partial<Record<keyof FormData, string>>;
    categories: Option[];
    days: Option[];
    idPrefix: string;
}) {
    return (
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-[10rem_1fr_8rem_7rem_1fr]">
            <div className="grid gap-1">
                <Label htmlFor={`${idPrefix}-category`} className="text-xs">
                    Kategori
                </Label>
                <select
                    id={`${idPrefix}-category`}
                    className={selectClass}
                    value={data.category}
                    onChange={(e) => setData('category', e.target.value)}
                >
                    {categories.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </select>
                <InputError message={errors.category} />
            </div>
            <div className="grid gap-1">
                <Label htmlFor={`${idPrefix}-name`} className="text-xs">
                    Nama kegiatan
                </Label>
                <Input
                    id={`${idPrefix}-name`}
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                />
                <InputError message={errors.name} />
            </div>
            <div className="grid gap-1">
                <Label htmlFor={`${idPrefix}-day`} className="text-xs">
                    Hari
                </Label>
                <select
                    id={`${idPrefix}-day`}
                    className={selectClass}
                    value={data.day}
                    onChange={(e) => setData('day', e.target.value)}
                >
                    {days.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </select>
                <InputError message={errors.day} />
            </div>
            <div className="grid gap-1">
                <Label htmlFor={`${idPrefix}-time`} className="text-xs">
                    Jam (WIB)
                </Label>
                <Input
                    id={`${idPrefix}-time`}
                    type="time"
                    value={data.start_time}
                    onChange={(e) => setData('start_time', e.target.value)}
                />
                <InputError message={errors.start_time} />
            </div>
            <div className="grid gap-1">
                <Label htmlFor={`${idPrefix}-location`} className="text-xs">
                    Lokasi (opsional)
                </Label>
                <Input
                    id={`${idPrefix}-location`}
                    value={data.location}
                    onChange={(e) => setData('location', e.target.value)}
                />
                <InputError message={errors.location} />
            </div>
        </div>
    );
}

function ScheduleRow({
    schedule,
    categories,
    days,
}: {
    schedule: Schedule;
    categories: Option[];
    days: Option[];
}) {
    const form = useForm<FormData>({
        category: schedule.category,
        name: schedule.name,
        day: String(schedule.day),
        start_time: schedule.startTime,
        location: schedule.location ?? '',
        is_active: schedule.isActive,
    });

    const save = (event?: FormEvent, overrides: Partial<FormData> = {}) => {
        event?.preventDefault();
        form.transform((data) => ({ ...data, ...overrides }));
        form.put(update.url(schedule.id), { preserveScroll: true });
    };

    return (
        <li
            className={cn(
                'rounded-xl border p-4',
                !schedule.isActive && 'bg-muted/40',
            )}
        >
            <form onSubmit={save} className="grid gap-3">
                <ScheduleFields
                    data={form.data}
                    setData={form.setData}
                    errors={form.errors}
                    categories={categories}
                    days={days}
                    idPrefix={`schedule-${schedule.id}`}
                />
                <div className="flex flex-wrap items-center gap-2">
                    <Button
                        type="submit"
                        size="sm"
                        disabled={form.processing || !form.isDirty}
                    >
                        <Save />
                        Simpan
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        onClick={() =>
                            save(undefined, { is_active: !schedule.isActive })
                        }
                    >
                        {schedule.isActive ? <EyeOff /> : <Eye />}
                        {schedule.isActive ? 'Sembunyikan' : 'Tampilkan'}
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        variant="ghost"
                        className="ml-auto"
                        onClick={() => {
                            if (
                                window.confirm(
                                    `Hapus jadwal "${schedule.name}"?`,
                                )
                            ) {
                                router.delete(destroy.url(schedule.id), {
                                    preserveScroll: true,
                                });
                            }
                        }}
                    >
                        <Trash2 />
                        Hapus
                    </Button>
                </div>
            </form>
        </li>
    );
}

/** Worship schedule manager (PRD 4.3). */
export default function LandingSchedule({
    schedules,
    categories,
    days,
    flash,
}: Props) {
    const [adding, setAdding] = useState(false);
    const form = useForm<FormData>({
        category: String(categories[0]?.value ?? 'raya'),
        name: '',
        day: '0',
        start_time: '08:00',
        location: '',
        is_active: true,
    });

    const grouped = categories.map((category) => ({
        ...category,
        items: schedules.filter(
            (schedule) => schedule.category === category.value,
        ),
    }));

    return (
        <>
            <Head title="Jadwal ibadah" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <LandingAdminNav />
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Jadwal ibadah"
                        description="Tampil di Home dan halaman Jadwal. Jadwal yang disembunyikan tidak tampil di situs."
                    />
                    {!adding && (
                        <Button onClick={() => setAdding(true)}>
                            <Plus />
                            Tambah jadwal
                        </Button>
                    )}
                </div>

                {flash && (
                    <p
                        role="status"
                        className="rounded-xl border border-green-600/30 bg-green-600/10 px-4 py-3 text-sm"
                    >
                        {flash}
                    </p>
                )}

                {adding && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Jadwal baru</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    form.post(store.url(), {
                                        preserveScroll: true,
                                        onSuccess: () => {
                                            form.reset();
                                            setAdding(false);
                                        },
                                    });
                                }}
                                className="grid gap-3"
                            >
                                <ScheduleFields
                                    data={form.data}
                                    setData={form.setData}
                                    errors={form.errors}
                                    categories={categories}
                                    days={days}
                                    idPrefix="new"
                                />
                                <div className="flex gap-2">
                                    <Button
                                        type="submit"
                                        disabled={form.processing}
                                    >
                                        Tambah
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        onClick={() => setAdding(false)}
                                    >
                                        Batal
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                )}

                {grouped.map((group) => (
                    <section
                        key={group.value}
                        aria-labelledby={`group-${group.value}`}
                        className="grid gap-3"
                    >
                        <h2
                            id={`group-${group.value}`}
                            className="font-semibold"
                        >
                            {group.label}
                        </h2>
                        {group.items.length === 0 ? (
                            <p className="rounded-xl border border-dashed p-4 text-sm text-muted-foreground">
                                Belum ada jadwal.
                            </p>
                        ) : (
                            <ul className="grid gap-3">
                                {group.items.map((schedule) => (
                                    // Remount after a save so the form starts from the stored values.
                                    <ScheduleRow
                                        key={JSON.stringify(schedule)}
                                        schedule={schedule}
                                        categories={categories}
                                        days={days}
                                    />
                                ))}
                            </ul>
                        )}
                    </section>
                ))}
            </div>
        </>
    );
}

LandingSchedule.layout = {
    breadcrumbs: [
        { title: 'Landing Page', href: '/admin/landing' },
        { title: 'Jadwal ibadah', href: '/admin/landing/jadwal' },
    ],
};
