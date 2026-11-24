import { router } from '@inertiajs/react';
import { useState } from 'react';

/**
 * State for a ConfirmDeleteDialog: the target outlives the open flag so the
 * dialog keeps its text while it animates closed.
 */
export function useDeleteTarget<T>() {
    const [open, setOpen] = useState(false);
    const [target, setTarget] = useState<T | null>(null);
    const [processing, setProcessing] = useState(false);

    const ask = (item: T) => {
        setTarget(item);
        setOpen(true);
    };

    const confirm = (url: string) => {
        router.delete(url, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => {
                setProcessing(false);
                setOpen(false);
            },
        });
    };

    return { open, setOpen, target, processing, ask, confirm };
}
