import { Link, usePage } from '@inertiajs/react';
import { LayoutGrid } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { moduleIcon } from '@/lib/module-icons';
import { dashboard } from '@/routes';
import type { ModuleNavItem, NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

// Module items come from the server (ModuleServiceProvider::navigation()),
// grouped by their `group` label in the order the server sent them.
function groupModuleItems(items: ModuleNavItem[]): [string, NavItem[]][] {
    const groups = new Map<string, NavItem[]>();

    for (const item of items) {
        const group = groups.get(item.group) ?? [];

        group.push({
            title: item.title,
            href: item.href,
            icon: moduleIcon(item.module, item.icon),
        });
        groups.set(item.group, group);
    }

    return [...groups.entries()];
}

export function AppSidebar() {
    const { navigation } = usePage().props;

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} label="Umum" />
                {groupModuleItems(navigation ?? []).map(([label, items]) => (
                    <NavMain key={label} items={items} label={label} />
                ))}
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
