import type { Role } from '@/types';

export const roleLabels: Record<Role, string> = {
    admin: 'Admin',
    sales: 'Sales',
    customer: 'Pelanggan',
};

export function isStaff(role: Role): boolean {
    return role === 'admin' || role === 'sales';
}
