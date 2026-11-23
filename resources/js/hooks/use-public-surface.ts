import { useEffect } from 'react';

export function usePublicSurface(): void {
    useEffect(() => {
        const root = document.documentElement;
        root.classList.add('surface-public');

        return () => root.classList.remove('surface-public');
    }, []);
}
