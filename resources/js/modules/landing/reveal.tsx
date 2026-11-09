import { useEffect, useRef, useState } from 'react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type Phase = 'shown' | 'hidden' | 'revealed';

/**
 * Fades and lifts content in as it scrolls into view. Content starts
 * visible (server render, first paint, no-JS); only blocks that begin below
 * the screen are hidden after mount and revealed on scroll. Blocks already
 * on screen never animate, so a refresh or page change does not flash.
 * Reduced-motion settings shorten the transition to nothing.
 */
export function Reveal({
    children,
    className,
}: {
    children: ReactNode;
    className?: string;
}) {
    const ref = useRef<HTMLDivElement>(null);
    const [phase, setPhase] = useState<Phase>('shown');

    useEffect(() => {
        const element = ref.current;

        if (!element || typeof IntersectionObserver === 'undefined') {
            return;
        }

        if (element.getBoundingClientRect().top < window.innerHeight) {
            return;
        }

        setPhase('hidden');

        const observer = new IntersectionObserver(
            ([entry]) => {
                if (entry?.isIntersecting) {
                    setPhase('revealed');
                    observer.disconnect();
                }
            },
            { rootMargin: '0px 0px -10% 0px' },
        );

        observer.observe(element);

        return () => observer.disconnect();
    }, []);

    return (
        <div
            ref={ref}
            className={cn(
                phase !== 'shown' && 'transition duration-700 ease-out',
                phase === 'hidden' && 'translate-y-6 opacity-0',
                className,
            )}
        >
            {children}
        </div>
    );
}
