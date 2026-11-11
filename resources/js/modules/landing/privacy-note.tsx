import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { privacy } from '@/routes/landing';

/** Consent line under forms that collect personal data. */
export function PrivacyNote({
    action,
    className,
}: {
    action: string;
    className?: string;
}) {
    return (
        <p className={cn('text-xs text-muted-foreground', className)}>
            Dengan {action}, Anda menyetujui pengolahan data sesuai{' '}
            <Link
                href={privacy()}
                className="underline underline-offset-4 hover:text-foreground"
            >
                Kebijakan Privasi
            </Link>{' '}
            GGP Elim.
        </p>
    );
}
