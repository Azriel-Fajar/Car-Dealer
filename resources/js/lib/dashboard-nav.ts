import type { NavItem, Role } from '@/types';

export type DashboardNavItem = NavItem & { roles: Role[] };

export function navItemsFor(
    role: Role,
    items: DashboardNavItem[],
): DashboardNavItem[] {
    return items.filter((item) => item.roles.includes(role));
}
