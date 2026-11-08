import type { InertiaLinkProps } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';

export type BreadcrumbItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
};

/** Sidebar item contributed by a backend module (app/Modules/*). */
export type ModuleNavItem = {
    module: string;
    title: string;
    href: string;
    icon: string | null;
    group: string;
};
