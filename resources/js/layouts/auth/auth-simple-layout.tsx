import { Link } from '@inertiajs/react';
import BrandWordmark from '@/components/public/brand-wordmark';
import { usePublicSurface } from '@/hooks/use-public-surface';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    usePublicSurface();

    return (
        <div className="surface-public dark relative isolate flex min-h-svh flex-col items-center justify-center gap-6 overflow-hidden bg-background p-6 text-foreground md:p-10">
            <div aria-hidden="true" className="absolute inset-0 -z-10">
                <div className="absolute -top-[30vh] left-1/2 size-[80vw] max-w-[900px] -translate-x-1/2 rounded-full bg-ember/15 blur-[140px]" />
                <div className="absolute inset-x-0 top-0 h-px bg-linear-to-r from-transparent via-ember/50 to-transparent" />
            </div>
            <div className="w-full max-w-sm">
                <div className="flex flex-col gap-8">
                    <div className="flex flex-col items-center gap-6">
                        <Link
                            href={home()}
                            className="rounded-sm text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                        >
                            <BrandWordmark className="text-2xl" />
                        </Link>

                        <div className="space-y-2 text-center">
                            <h1 className="font-display text-4xl leading-none font-extrabold uppercase">
                                {title}
                            </h1>
                            <p className="text-center text-sm text-muted-foreground">
                                {description}
                            </p>
                        </div>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
