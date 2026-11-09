import type { SVGAttributes } from 'react';

/**
 * Temporary GGP Elim flame mark. Replace with the official wordmark once the
 * vector file is available (PRD 4.9, "Identitas dari logo").
 */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg
            {...props}
            viewBox="0 0 24 24"
            xmlns="http://www.w3.org/2000/svg"
            aria-hidden="true"
        >
            <path d="M12.4 1.5c.5 3.1 2.3 4.9 4 6.7 1.7 1.8 3.1 3.8 3.1 6.6A7.5 7.5 0 0 1 12 22.3a7.5 7.5 0 0 1-7.5-7.5c0-2.6 1.2-4.6 2.8-6.1.1 1.9.9 3.3 2.4 4-.9-4.1.4-8.2 2.7-11.2Zm-.9 11.6c-1.6 1.3-2.6 2.8-2.6 4.6a3.1 3.1 0 0 0 6.2 0c0-1.5-.8-2.6-1.8-3.6-.4.8-1 1.3-1.8 1.4.3-.8.3-1.6 0-2.4Z" />
        </svg>
    );
}
