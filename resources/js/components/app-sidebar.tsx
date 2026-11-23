import { Link, usePage } from '@inertiajs/react';
import { ExternalLink, LayoutGrid, Tags } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
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
import { navItemsFor } from '@/lib/dashboard-nav';
import type { DashboardNavItem } from '@/lib/dashboard-nav';
import { dashboard, home } from '@/routes';
import { index as brandsIndex } from '@/routes/dashboard/brands';
import type { NavItem } from '@/types';

const mainNavItems: DashboardNavItem[] = [
    {
        title: 'Ringkasan',
        href: dashboard(),
        icon: LayoutGrid,
        roles: ['admin', 'sales'],
    },
];

const masterDataNavItems: DashboardNavItem[] = [
    {
        title: 'Brand',
        href: brandsIndex(),
        icon: Tags,
        roles: ['admin'],
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Lihat Situs',
        href: home(),
        icon: ExternalLink,
    },
];

export function AppSidebar() {
    const { auth } = usePage().props;
    const navItems = navItemsFor(auth.user.role, mainNavItems);
    const masterDataItems = navItemsFor(auth.user.role, masterDataNavItems);

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
                <NavMain items={navItems} />
                {masterDataItems.length > 0 && (
                    <NavMain items={masterDataItems} label="Master Data" />
                )}
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
