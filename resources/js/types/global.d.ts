import type { Auth } from '@/types/auth';
import type { ModuleNavItem } from '@/types/navigation';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            navigation: ModuleNavItem[];
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
