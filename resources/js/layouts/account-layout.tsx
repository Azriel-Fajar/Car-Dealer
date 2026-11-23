import { Link, router } from '@inertiajs/react';
import { LogOut } from 'lucide-react';
import SiteFooter from '@/components/public/site-footer';
import SiteHeader from '@/components/public/site-header';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { usePublicSurface } from '@/hooks/use-public-surface';
import { cn } from '@/lib/utils';
import { account, logout } from '@/routes';
import { edit } from '@/routes/profile';
import type { BreadcrumbItem } from '@/types';

const tabClass =
    'relative inline-flex items-center gap-2 py-4 label-spec whitespace-nowrap transition-colors hover:text-foreground focus-visible:text-foreground focus-visible:outline-none';
const activeTabClass =
    'text-foreground after:absolute after:inset-x-0 after:-bottom-px after:h-px after:bg-ember';

export default function AccountLayout({
    children,
}: {
    breadcrumbs?: BreadcrumbItem[];
    children: React.ReactNode;
}) {
    usePublicSurface();
    const { currentUrl, isCurrentUrl } = useCurrentUrl();

    const items = [
        {
            title: 'Ringkasan',
            href: account(),
            active: isCurrentUrl(account()),
        },
        {
            title: 'Pengaturan',
            href: edit(),
            active: currentUrl.startsWith('/settings'),
        },
    ];

    return (
        <div className="surface-public dark flex min-h-svh flex-col bg-background font-sans text-foreground">
            <a
                href="#konten"
                className="sr-only rounded-full bg-primary px-5 py-2 text-sm font-semibold text-primary-foreground focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50"
            >
                Lewati ke konten
            </a>
            <SiteHeader />
            <div className="mx-auto w-full max-w-screen-2xl flex-1 px-5 pt-16 md:px-10 md:pt-20">
                <nav
                    aria-label="Akun"
                    className="mt-6 border-b border-border md:mt-10"
                >
                    <ul className="flex items-center gap-6 md:gap-8">
                        {items.map((item) => (
                            <li key={item.title}>
                                <Link
                                    href={item.href}
                                    aria-current={
                                        item.active ? 'page' : undefined
                                    }
                                    className={cn(
                                        tabClass,
                                        item.active
                                            ? activeTabClass
                                            : 'text-muted-foreground',
                                    )}
                                >
                                    {item.title}
                                </Link>
                            </li>
                        ))}
                        <li className="ml-auto">
                            <Link
                                href={logout()}
                                as="button"
                                onClick={() => router.flushAll()}
                                data-test="logout-button"
                                className={cn(
                                    tabClass,
                                    'cursor-pointer text-muted-foreground hover:text-ember',
                                )}
                            >
                                <LogOut
                                    aria-hidden="true"
                                    className="size-3.5"
                                />
                                Keluar
                            </Link>
                        </li>
                    </ul>
                </nav>
                <main
                    id="konten"
                    tabIndex={-1}
                    className="py-10 outline-none md:py-14"
                >
                    {children}
                </main>
            </div>
            <SiteFooter />
        </div>
    );
}
