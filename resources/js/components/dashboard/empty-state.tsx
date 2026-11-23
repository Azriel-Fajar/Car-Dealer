import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';

export function EmptyState({
    icon: Icon,
    title,
    description,
    action,
}: {
    icon: LucideIcon;
    title: string;
    description: string;
    action?: ReactNode;
}) {
    return (
        <div className="flex flex-col items-center justify-center gap-3 px-6 py-16 text-center">
            <span className="flex size-12 items-center justify-center rounded-xl bg-muted text-muted-foreground">
                <Icon aria-hidden="true" className="size-5" />
            </span>
            <div className="space-y-1">
                <p className="font-semibold">{title}</p>
                <p className="max-w-sm text-sm text-muted-foreground">
                    {description}
                </p>
            </div>
            {action}
        </div>
    );
}
