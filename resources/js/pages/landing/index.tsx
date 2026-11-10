import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowDown,
    ArrowUp,
    Eye,
    EyeOff,
    GripVertical,
    Pencil,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import type { DragEvent } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { LandingAdminNav } from '@/modules/landing/admin-nav';
import { order, visibility, edit } from '@/routes/landing/sections';
import { edit as segments } from '@/routes/landing/segments';

type Section = {
    key: string;
    label: string;
    place: string | null;
    isActive: boolean;
    editor: 'section' | 'segment' | null;
};

type Props = {
    sections: Section[];
    flash: string | null;
};

/**
 * Landing layout (PRD 4.8): drag sections to reorder (or use the arrows),
 * show or hide them, and open each one's text editor.
 */
export default function LandingLayout({ sections, flash }: Props) {
    const [items, setItems] = useState(sections);
    const [dragging, setDragging] = useState<string | null>(null);

    useEffect(() => setItems(sections), [sections]);

    const save = (next: Section[]) => {
        setItems(next);
        router.post(
            order.url(),
            { keys: next.map((item) => item.key) },
            { preserveScroll: true },
        );
    };

    const move = (index: number, step: number) => {
        const target = index + step;

        if (target < 0 || target >= items.length) {
            return;
        }

        const next = [...items];
        [next[index], next[target]] = [next[target], next[index]];
        save(next);
    };

    const onDragOver = (event: DragEvent, key: string) => {
        event.preventDefault();

        if (!dragging || dragging === key) {
            return;
        }

        const from = items.findIndex((item) => item.key === dragging);
        const to = items.findIndex((item) => item.key === key);
        const next = [...items];
        const [moved] = next.splice(from, 1);
        next.splice(to, 0, moved);
        setItems(next);
    };

    const onDrop = () => {
        if (dragging) {
            save(items);
        }

        setDragging(null);
    };

    return (
        <>
            <Head title="Landing Page" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <LandingAdminNav />
                <Heading
                    title="Tata letak & konten"
                    description="Seret untuk mengubah urutan, sembunyikan section, atau ubah isinya. Perubahan langsung tampil di situs."
                />

                {flash && (
                    <p
                        role="status"
                        className="rounded-xl border border-green-600/30 bg-green-600/10 px-4 py-3 text-sm"
                    >
                        {flash}
                    </p>
                )}

                <ol className="grid gap-2" aria-label="Urutan section">
                    {items.map((item, index) => (
                        <li
                            key={item.key}
                            draggable
                            onDragStart={() => setDragging(item.key)}
                            onDragOver={(event) => onDragOver(event, item.key)}
                            onDrop={onDrop}
                            onDragEnd={() => setDragging(null)}
                            className={cn(
                                'flex flex-wrap items-center gap-3 rounded-xl border bg-background p-3 transition-shadow',
                                dragging === item.key &&
                                    'opacity-60 shadow-lg ring-2 ring-primary',
                                !item.isActive && 'bg-muted/40',
                            )}
                        >
                            <GripVertical
                                className="size-4 shrink-0 cursor-grab text-muted-foreground"
                                aria-hidden="true"
                            />
                            <span className="w-6 text-right text-sm text-muted-foreground tabular-nums">
                                {index + 1}
                            </span>
                            <div className="min-w-0 flex-1">
                                <div
                                    className={cn(
                                        'font-medium',
                                        !item.isActive &&
                                            'text-muted-foreground line-through',
                                    )}
                                >
                                    {item.label}
                                </div>
                                {item.place && (
                                    <div className="text-xs text-muted-foreground">
                                        Tampil di: {item.place}
                                    </div>
                                )}
                            </div>
                            {!item.isActive && (
                                <Badge variant="secondary">Disembunyikan</Badge>
                            )}
                            <div className="flex items-center gap-1">
                                <Button
                                    size="icon"
                                    variant="ghost"
                                    disabled={index === 0}
                                    onClick={() => move(index, -1)}
                                    aria-label={`Naikkan ${item.label}`}
                                >
                                    <ArrowUp />
                                </Button>
                                <Button
                                    size="icon"
                                    variant="ghost"
                                    disabled={index === items.length - 1}
                                    onClick={() => move(index, 1)}
                                    aria-label={`Turunkan ${item.label}`}
                                >
                                    <ArrowDown />
                                </Button>
                                <Button
                                    size="icon"
                                    variant="ghost"
                                    onClick={() =>
                                        router.post(
                                            visibility.url(item.key),
                                            { is_active: !item.isActive },
                                            { preserveScroll: true },
                                        )
                                    }
                                    aria-label={
                                        item.isActive
                                            ? `Sembunyikan ${item.label}`
                                            : `Tampilkan ${item.label}`
                                    }
                                >
                                    {item.isActive ? <Eye /> : <EyeOff />}
                                </Button>
                                {item.editor && (
                                    <Button asChild size="sm" variant="outline">
                                        <Link
                                            href={
                                                item.editor === 'section'
                                                    ? edit.url(item.key)
                                                    : segments.url()
                                            }
                                        >
                                            <Pencil />
                                            Ubah
                                        </Link>
                                    </Button>
                                )}
                            </div>
                        </li>
                    ))}
                </ol>

                <p className="text-xs text-muted-foreground">
                    Urutan berlaku untuk section di halaman Home. Section
                    Tentang, Misi, Form Doa, dan Contact tampil di halamannya
                    masing-masing.
                </p>
            </div>
        </>
    );
}

LandingLayout.layout = {
    breadcrumbs: [{ title: 'Landing Page', href: '/admin/landing' }],
};
