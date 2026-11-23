import type { InertiaLinkProps } from '@inertiajs/react';
import { isStaff } from '@/lib/roles';
import { account, dashboard, home, login, register } from '@/routes';
import type { User } from '@/types';

export const publicNavItems: {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
}[] = [{ title: 'Beranda', href: home() }];

export type AuthNavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    primary?: boolean;
};

export function authNavItems(user: User | null): AuthNavItem[] {
    if (!user) {
        return [
            { title: 'Masuk', href: login() },
            { title: 'Daftar', href: register(), primary: true },
        ];
    }

    return isStaff(user.role)
        ? [{ title: 'Dashboard', href: dashboard() }]
        : [{ title: 'Akun Saya', href: account() }];
}
