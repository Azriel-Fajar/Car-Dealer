import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import BrandWordmark from '@/components/public/brand-wordmark';
import MobileMenu from '@/components/public/mobile-menu';
import { authNavItems, publicNavItems } from '@/components/public/nav-items';
import { Button } from '@/components/ui/button';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn } from '@/lib/utils';
import { home } from '@/routes';

const SCROLL_THRESHOLD = 24;

export default function SiteHeader() {
    const { auth } = usePage().props;
    const { isCurrentUrl } = useCurrentUrl();
    const [scrolled, setScrolled] = useState(false);
    const authItems = authNavItems(auth.user ?? null);

    useEffect(() => {
        const onScroll = () => setScrolled(window.scrollY > SCROLL_THRESHOLD);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });

        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    return (
        <header
            className={cn(
                'fixed inset-x-0 top-0 z-40 border-b transition-[background-color,border-color] duration-300',
                scrolled
                    ? 'border-border bg-background/80 backdrop-blur'
                    : 'border-transparent bg-transparent',
            )}
        >
            <div className="mx-auto flex h-16 max-w-screen-2xl items-center gap-10 px-5 md:h-20 md:px-10">
                <Link
                    href={home()}
                    className="rounded-sm text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                >
                    <BrandWordmark />
                </Link>

                <nav aria-label="Utama" className="hidden md:block">
                    <ul className="flex items-center gap-8">
                        {publicNavItems.map((item) => {
                            const active = isCurrentUrl(item.href);

                            return (
                                <li key={item.title}>
                                    <Link
                                        href={item.href}
                                        aria-current={
                                            active ? 'page' : undefined
                                        }
                                        className={cn(
                                            'relative py-2 label-spec transition-colors hover:text-foreground',
                                            active
                                                ? 'text-foreground after:absolute after:inset-x-0 after:bottom-0 after:h-px after:bg-ember'
                                                : 'text-muted-foreground',
                                        )}
                                    >
                                        {item.title}
                                    </Link>
                                </li>
                            );
                        })}
                    </ul>
                </nav>

                <div className="ml-auto hidden items-center gap-2 md:flex">
                    {authItems.map((item) => (
                        <Button
                            key={item.title}
                            asChild
                            variant={item.primary ? 'default' : 'ghost'}
                            className="rounded-full px-5 font-semibold"
                        >
                            <Link href={item.href}>{item.title}</Link>
                        </Button>
                    ))}
                </div>

                <div className="ml-auto md:hidden">
                    <MobileMenu authItems={authItems} />
                </div>
            </div>
        </header>
    );
}
