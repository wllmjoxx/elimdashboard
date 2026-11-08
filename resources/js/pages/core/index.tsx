import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';

export default function CoreIndex() {
    return (
        <>
            <Head title="Cabang & Akses" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <Heading
                    title="Cabang & Akses"
                    description="Modul ini belum dibangun."
                />
            </div>
        </>
    );
}

CoreIndex.layout = {
    breadcrumbs: [{ title: 'Cabang & Akses', href: '/admin/core' }],
};
