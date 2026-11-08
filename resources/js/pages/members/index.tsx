import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';

export default function MembersIndex() {
    return (
        <>
            <Head title="Jemaat" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <Heading
                    title="Jemaat"
                    description="Modul ini belum dibangun."
                />
            </div>
        </>
    );
}

MembersIndex.layout = {
    breadcrumbs: [{ title: 'Jemaat', href: '/admin/members' }],
};
