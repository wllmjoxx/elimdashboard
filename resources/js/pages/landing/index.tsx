import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';

export default function LandingIndex() {
    return (
        <>
            <Head title="Landing Page" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <Heading
                    title="Landing Page"
                    description="Modul ini belum dibangun."
                />
            </div>
        </>
    );
}

LandingIndex.layout = {
    breadcrumbs: [{ title: 'Landing Page', href: '/admin/landing' }],
};
