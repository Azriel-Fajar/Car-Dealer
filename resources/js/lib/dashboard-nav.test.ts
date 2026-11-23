import { describe, expect, it } from 'vite-plus/test';
import { navItemsFor } from './dashboard-nav';
import type { DashboardNavItem } from './dashboard-nav';

const items: DashboardNavItem[] = [
    { title: 'Ringkasan', href: '/dashboard', roles: ['admin', 'sales'] },
    { title: 'Pengguna', href: '/dashboard/users', roles: ['admin'] },
];

describe('navItemsFor', () => {
    it('shows admin every item', () =>
        expect(navItemsFor('admin', items).map((i) => i.title)).toEqual([
            'Ringkasan',
            'Pengguna',
        ]));
    it('hides admin-only items from sales', () =>
        expect(navItemsFor('sales', items).map((i) => i.title)).toEqual([
            'Ringkasan',
        ]));
    it('shows customers nothing', () =>
        expect(navItemsFor('customer', items)).toEqual([]));
});
