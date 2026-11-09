import { useEffect, useRef, useState } from 'react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

/**
 * Fades and lifts its content in when it scrolls into view. Content is in
 * the page from the start (for search engines); only its look is animated,
 * and reduced-motion settings shorten the transition to nothing.
 */
export function Reveal({
    children,
    className,
}: {
    children: ReactNode;
    className?: string;
}) {
    const ref = useRef<HTMLDivElement>(null);
    const [visible, setVisible] = useState(false);

    useEffect(() => {
        const element = ref.current;

        if (!element || typeof IntersectionObserver === 'undefined') {
            setVisible(true);

            return;
        }

        const observer = new IntersectionObserver(
            ([entry]) => {
                if (entry?.isIntersecting) {
                    setVisible(true);
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
                'transition duration-700 ease-out',
                visible
                    ? 'translate-y-0 opacity-100'
                    : 'translate-y-6 opacity-0',
                className,
            )}
        >
            {children}
        </div>
    );
}
