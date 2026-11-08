import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';

export default function PrayerIndex() {
    return (
        <>
            <Head title="Doa Mingguan" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <Heading
                    title="Doa Mingguan"
                    description="Modul ini belum dibangun."
                />
            </div>
        </>
    );
}

PrayerIndex.layout = {
    breadcrumbs: [{ title: 'Doa Mingguan', href: '/admin/prayer' }],
};
