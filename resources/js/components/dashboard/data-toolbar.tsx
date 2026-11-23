import { Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { ReactNode } from 'react';
import { Input } from '@/components/ui/input';

const SEARCH_DEBOUNCE_MS = 300;

export function DataToolbar({
    search,
    onSearch,
    filters,
    actions,
}: {
    search: string;
    onSearch: (q: string) => void;
    filters?: ReactNode;
    actions?: ReactNode;
}) {
    const [value, setValue] = useState(search);
    const timer = useRef<number | undefined>(undefined);

    useEffect(() => () => window.clearTimeout(timer.current), []);

    const handleChange = (q: string) => {
        setValue(q);
        window.clearTimeout(timer.current);
        timer.current = window.setTimeout(
            () => onSearch(q.trim()),
            SEARCH_DEBOUNCE_MS,
        );
    };

    return (
        <div className="flex flex-wrap items-center gap-3">
            <div className="relative w-full sm:w-72">
                <Search
                    aria-hidden="true"
                    className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    type="search"
                    value={value}
                    onChange={(event) => handleChange(event.target.value)}
                    placeholder="Cari…"
                    aria-label="Cari"
                    className="h-10 rounded-xl bg-card pl-9"
                />
            </div>
            {filters && (
                <div className="flex flex-wrap items-center gap-2">
                    {filters}
                </div>
            )}
            {actions && (
                <div className="flex w-full items-center gap-2 sm:ml-auto sm:w-auto">
                    {actions}
                </div>
            )}
        </div>
    );
}
