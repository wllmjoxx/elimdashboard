import type { HTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

type GlassElement =
    | 'div'
    | 'section'
    | 'article'
    | 'aside'
    | 'header'
    | 'nav'
    | 'footer';

export type GlassProps = HTMLAttributes<HTMLElement> & {
    as?: GlassElement;
};

/**
 * Liquid glass surface (PRD 4.9): soft blur, thin transparency, hairline
 * border and a 24px radius. Use it for floating layers only (navbar, cards,
 * form panels, modals) and keep at most two glass layers stacked.
 */
export function Glass({
    as: Component = 'div',
    className,
    ...props
}: GlassProps) {
    return (
        <Component
            data-slot="glass"
            className={cn('rounded-glass glass', className)}
            {...props}
        />
    );
}
