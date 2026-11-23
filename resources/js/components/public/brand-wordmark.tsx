import { usePage } from '@inertiajs/react';
import { cn } from '@/lib/utils';

export default function BrandWordmark({ className }: { className?: string }) {
    const { name } = usePage().props;

    return (
        <span
            className={cn(
                'inline-flex items-center gap-2 font-display text-xl leading-none font-extrabold tracking-[0.06em] uppercase',
                className,
            )}
        >
            <span
                aria-hidden="true"
                className="size-2 shrink-0 rotate-45 bg-ember"
            />
            {name}
        </span>
    );
}
