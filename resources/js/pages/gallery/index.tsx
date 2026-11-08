import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';

export default function GalleryIndex() {
    return (
        <>
            <Head title="Galeri" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <Heading
                    title="Galeri"
                    description="Modul ini belum dibangun."
                />
            </div>
        </>
    );
}

GalleryIndex.layout = {
    breadcrumbs: [{ title: 'Galeri', href: '/admin/gallery' }],
};
