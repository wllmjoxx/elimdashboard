import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';

/** Profile photo, or the member's initials when there is none. */
export function MemberAvatar({
    name,
    src,
    className,
}: {
    name: string;
    src: string | null;
    className?: string;
}) {
    const initials = useInitials();

    return src ? (
        <img
            src={src}
            alt={`Foto ${name}`}
            className={cn('rounded-full object-cover', className)}
        />
    ) : (
        <span
            aria-hidden="true"
            className={cn(
                'grid place-items-center rounded-full bg-muted font-semibold text-muted-foreground',
                className,
            )}
        >
            {initials(name)}
        </span>
    );
}
