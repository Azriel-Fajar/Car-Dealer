import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types';

const pageClass =
    'inline-flex h-9 min-w-9 items-center justify-center rounded-lg px-2 text-sm font-medium transition-colors';

export function PaginationBar({ meta }: { meta: Paginated<unknown> }) {
    if (meta.total === 0) {
        return null;
    }

    // Laravel's links: [previous, ...pages, next].
    const prev = meta.links[0];
    const next = meta.links[meta.links.length - 1];
    const pages = meta.links.slice(1, -1);

    return (
        <nav
            aria-label="Paginasi"
            className="flex flex-wrap items-center justify-between gap-3"
        >
            <p className="text-sm text-muted-foreground">
                Menampilkan {meta.from}–{meta.to} dari {meta.total}
            </p>
            {meta.last_page > 1 && (
                <div className="flex flex-wrap items-center gap-1">
                    <PageLink url={prev?.url ?? null} label="Sebelumnya">
                        <ChevronLeft className="size-4" />
                    </PageLink>
                    {pages.map((link, index) =>
                        link.url === null ? (
                            <span
                                key={`gap-${index}`}
                                className={cn(
                                    pageClass,
                                    'text-muted-foreground',
                                )}
                            >
                                {link.label}
                            </span>
                        ) : (
                            <Link
                                key={link.url}
                                href={link.url}
                                preserveScroll
                                aria-current={link.active ? 'page' : undefined}
                                className={cn(
                                    pageClass,
                                    link.active
                                        ? 'bg-primary text-primary-foreground'
                                        : 'hover:bg-muted',
                                )}
                            >
                                {link.label}
                            </Link>
                        ),
                    )}
                    <PageLink url={next?.url ?? null} label="Berikutnya">
                        <ChevronRight className="size-4" />
                    </PageLink>
                </div>
            )}
        </nav>
    );
}

function PageLink({
    url,
    label,
    children,
}: {
    url: string | null;
    label: string;
    children: ReactNode;
}) {
    if (url === null) {
        return (
            <span
                aria-hidden="true"
                className={cn(pageClass, 'text-muted-foreground/50')}
            >
                {children}
            </span>
        );
    }

    return (
        <Link
            href={url}
            preserveScroll
            aria-label={label}
            className={cn(pageClass, 'hover:bg-muted')}
        >
            {children}
        </Link>
    );
}
