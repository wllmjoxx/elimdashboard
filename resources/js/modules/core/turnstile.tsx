import { useEffect, useRef } from 'react';

type TurnstileApi = {
    render: (
        element: HTMLElement,
        options: {
            sitekey: string;
            callback: (token: string) => void;
            'expired-callback'?: () => void;
            'error-callback'?: () => void;
            theme?: 'auto' | 'light' | 'dark';
            language?: string;
        },
    ) => string;
    reset: (widgetId?: string) => void;
    remove: (widgetId: string) => void;
};

declare global {
    interface Window {
        turnstile?: TurnstileApi;
    }
}

const SCRIPT_URL =
    'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';

let scriptPromise: Promise<void> | null = null;

function loadScript(): Promise<void> {
    if (window.turnstile) {
        return Promise.resolve();
    }

    scriptPromise ??= new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = SCRIPT_URL;
        script.async = true;
        script.onload = () => resolve();
        script.onerror = () => {
            scriptPromise = null;
            reject(new Error('Turnstile gagal dimuat'));
        };
        document.head.appendChild(script);
    });

    return scriptPromise;
}

/**
 * Cloudflare Turnstile widget for public forms (PRD 4.6, 4.7). Renders
 * nothing when the server has no site key (captcha off). `resetKey` forces
 * a fresh challenge, e.g. after a failed submit.
 */
export function Turnstile({
    siteKey,
    onToken,
    resetKey = 0,
}: {
    siteKey: string | null;
    onToken: (token: string) => void;
    resetKey?: number;
}) {
    const container = useRef<HTMLDivElement>(null);
    const callback = useRef(onToken);

    useEffect(() => {
        callback.current = onToken;
    }, [onToken]);

    useEffect(() => {
        if (!siteKey || !container.current) {
            return;
        }

        let widgetId: string | undefined;
        let cancelled = false;

        loadScript()
            .then(() => {
                if (cancelled || !container.current || !window.turnstile) {
                    return;
                }

                widgetId = window.turnstile.render(container.current, {
                    sitekey: siteKey,
                    language: 'id',
                    theme: 'auto',
                    callback: (token) => callback.current(token),
                    'expired-callback': () => callback.current(''),
                    'error-callback': () => callback.current(''),
                });
            })
            .catch(() => callback.current(''));

        return () => {
            cancelled = true;

            if (widgetId && window.turnstile) {
                window.turnstile.remove(widgetId);
            }
        };
    }, [siteKey, resetKey]);

    if (!siteKey) {
        return null;
    }

    return <div ref={container} className="min-h-[65px]" />;
}
