import { usePage } from '@inertiajs/react';
import AccountLayout from '@/layouts/account-layout';
import DashboardLayout from '@/layouts/dashboard-layout';
import { isStaff } from '@/lib/roles';
import type { BreadcrumbItem } from '@/types';

export default function RoleLayout(props: {
    breadcrumbs?: BreadcrumbItem[];
    children: React.ReactNode;
}) {
    const { auth } = usePage().props;

    return isStaff(auth.user.role) ? (
        <DashboardLayout {...props} />
    ) : (
        <AccountLayout {...props} />
    );
}
