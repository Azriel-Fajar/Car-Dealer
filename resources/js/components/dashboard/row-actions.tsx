import { MoreHorizontal } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

export type RowAction = {
    label: string;
    onSelect: () => void;
    destructive?: boolean;
    icon?: LucideIcon;
};

export function RowActions({ items }: { items: RowAction[] }) {
    return (
        <DropdownMenu modal={false}>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="size-8 rounded-lg"
                    aria-label="Aksi"
                >
                    <MoreHorizontal />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="min-w-40">
                {items.map(({ label, onSelect, destructive, icon: Icon }) => (
                    <DropdownMenuItem
                        key={label}
                        variant={destructive ? 'destructive' : 'default'}
                        onSelect={onSelect}
                    >
                        {Icon && <Icon />}
                        {label}
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
