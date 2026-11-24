import { formatRupiah } from '@/lib/format';

/** Matches the server rule `mimes:jpg,jpeg,png,webp` (SVG is never accepted). */
export const IMAGE_ACCEPT = 'image/jpeg,image/png,image/webp';

/** A color's price difference, e.g. "+ Rp 5.000.000" or "- Rp 2.000.000". */
export function formatDelta(amount: number): string {
    return amount < 0
        ? `- ${formatRupiah(-amount)}`
        : `+ ${formatRupiah(amount)}`;
}
