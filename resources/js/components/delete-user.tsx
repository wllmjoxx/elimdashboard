import { Link } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { edit as memberProfile } from '@/routes/members/profile';

// Accounts are never hard-deleted by their owner. Members ask Admin from
// their profile page (PRD 4.5), so personal data is removed on purpose and
// the decision is recorded.
export default function DeleteUser() {
    return (
        <div className="space-y-6">
            <Heading
                variant="small"
                title="Hapus akun"
                description="Hapus akun dan data pribadi Anda dari GGP Elim"
            />
            <div className="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
                <p className="text-sm text-red-600 dark:text-red-100">
                    Penghapusan akun diproses oleh Admin. Ajukan permintaan dari
                    halaman Profil; akun tetap bisa dipakai sampai permintaan
                    diproses, dan Anda bisa membatalkannya kapan saja.
                </p>

                <Button
                    variant="destructive"
                    asChild
                    data-test="delete-user-button"
                >
                    <Link href={memberProfile()}>
                        <Trash2 />
                        Ajukan penghapusan akun
                    </Link>
                </Button>
            </div>
        </div>
    );
}
