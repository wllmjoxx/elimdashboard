import { router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { disconnect, update } from '@/routes/gallery/source';

export type GallerySource = {
    mode: 'manual' | 'instagram';
    connected: boolean;
    username: string | null;
    expiresOn: string | null;
};

const options = [
    {
        value: 'manual',
        label: 'Album',
        description: 'Album dan foto yang diunggah tim di halaman ini.',
    },
    {
        value: 'instagram',
        label: 'Instagram',
        description:
            'Postingan terbaru akun Instagram gereja, diperbarui setiap jam.',
    },
] as const;

/**
 * Where the public gallery and the homepage gallery take their photos from.
 * The token is never sent back to the browser.
 */
export function GallerySourceCard({ source }: { source: GallerySource }) {
    const form = useForm<{ source: GallerySource['mode']; token: string }>({
        source: source.mode,
        token: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(update.url(), {
            preserveScroll: true,
            onSuccess: () => form.setData('token', ''),
        });
    };

    return (
        <Card>
            <CardHeader>
                <div className="flex flex-wrap items-center gap-2">
                    <CardTitle>Sumber galeri</CardTitle>
                    {source.mode === 'instagram' ? (
                        <Badge>Instagram @{source.username}</Badge>
                    ) : (
                        <Badge variant="outline">Album</Badge>
                    )}
                </div>
                <CardDescription>
                    Menentukan isi halaman Galeri dan galeri di Beranda.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form onSubmit={submit} className="grid gap-5">
                    <fieldset className="grid gap-3 sm:grid-cols-2">
                        <legend className="sr-only">Sumber galeri</legend>
                        {options.map((option) => (
                            <label
                                key={option.value}
                                className="flex cursor-pointer gap-3 rounded-xl border p-4 has-[:checked]:border-primary has-[:checked]:bg-primary/5"
                            >
                                <input
                                    type="radio"
                                    name="source"
                                    value={option.value}
                                    checked={form.data.source === option.value}
                                    onChange={() =>
                                        form.setData('source', option.value)
                                    }
                                    className="mt-1 accent-[var(--primary)]"
                                />
                                <span>
                                    <span className="block font-medium">
                                        {option.label}
                                    </span>
                                    <span className="block text-sm text-muted-foreground">
                                        {option.description}
                                    </span>
                                </span>
                            </label>
                        ))}
                    </fieldset>

                    {form.data.source === 'instagram' && (
                        <div className="grid gap-2">
                            <Label htmlFor="ig-token">
                                Access token Instagram
                                {source.connected && (
                                    <span className="font-normal text-muted-foreground">
                                        {' '}
                                        (kosongkan untuk tetap memakai token
                                        sekarang)
                                    </span>
                                )}
                            </Label>
                            <Input
                                id="ig-token"
                                type="password"
                                autoComplete="off"
                                spellCheck={false}
                                placeholder={
                                    source.connected
                                        ? 'Token tersimpan'
                                        : 'IGAA…'
                                }
                                value={form.data.token}
                                onChange={(e) =>
                                    form.setData('token', e.target.value)
                                }
                            />
                            <InputError message={form.errors.token} />
                            <p className="text-sm text-muted-foreground">
                                Butuh akun Instagram profesional (Bisnis atau
                                Kreator) dan aplikasi di Meta for Developers.
                                Token diperbarui otomatis setiap hari sebelum
                                kedaluwarsa
                                {source.expiresOn &&
                                    ` (berlaku sampai ${source.expiresOn})`}
                                .
                            </p>
                        </div>
                    )}

                    <div className="flex flex-wrap gap-2">
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? 'Memeriksa…' : 'Simpan'}
                        </Button>
                        {source.connected && (
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => {
                                    if (
                                        window.confirm(
                                            'Putuskan akun Instagram? Galeri kembali memakai album.',
                                        )
                                    ) {
                                        router.delete(disconnect.url(), {
                                            preserveScroll: true,
                                        });
                                    }
                                }}
                            >
                                Putuskan Instagram
                            </Button>
                        )}
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}
