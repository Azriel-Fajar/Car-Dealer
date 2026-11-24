import type { StatusTone } from '@/components/dashboard/status-pill';

export function carStatusTone(status: string): StatusTone {
    if (status === 'published') {
        return 'success';
    }

    return status === 'sold_out' ? 'warning' : 'neutral';
}
