import type { ReactNode } from 'react';

export function DataTableShell({ children }: { children: ReactNode }) {
    return (
        <div className="overflow-hidden rounded-2xl border border-border bg-card text-card-foreground">
            <div className="overflow-x-auto">{children}</div>
        </div>
    );
}
