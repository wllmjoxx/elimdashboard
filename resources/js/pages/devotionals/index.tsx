import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';

export default function DevotionalsIndex() {
    return (
        <>
            <Head title="Renungan" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <Heading
                    title="Renungan"
                    description="Modul ini belum dibangun."
                />
            </div>
        </>
    );
}

DevotionalsIndex.layout = {
    breadcrumbs: [{ title: 'Renungan', href: '/admin/devotionals' }],
};
