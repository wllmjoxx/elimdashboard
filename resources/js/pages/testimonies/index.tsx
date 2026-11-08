import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';

export default function TestimoniesIndex() {
    return (
        <>
            <Head title="Kesaksian" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <Heading
                    title="Kesaksian"
                    description="Modul ini belum dibangun."
                />
            </div>
        </>
    );
}

TestimoniesIndex.layout = {
    breadcrumbs: [{ title: 'Kesaksian', href: '/admin/testimonies' }],
};
