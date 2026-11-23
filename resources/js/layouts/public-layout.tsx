import SiteFooter from '@/components/public/site-footer';
import SiteHeader from '@/components/public/site-header';
import { usePublicSurface } from '@/hooks/use-public-surface';
import { useLenis } from '@/lib/motion/hooks';

export default function PublicLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    usePublicSurface();
    useLenis();

    return (
        <div className="surface-public dark flex min-h-svh flex-col bg-background font-sans text-foreground">
            <a
                href="#konten"
                className="sr-only rounded-full bg-primary px-5 py-2 text-sm font-semibold text-primary-foreground focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50"
            >
                Lewati ke konten
            </a>
            <SiteHeader />
            <main id="konten" tabIndex={-1} className="flex-1 outline-none">
                {children}
            </main>
            <SiteFooter />
        </div>
    );
}
