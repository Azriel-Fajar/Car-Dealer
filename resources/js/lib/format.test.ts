import { describe, expect, it } from 'vite-plus/test';
import { formatRupiah, formatRupiahShort } from './format';

const vectors: [number, string, string][] = [
    [0, 'Rp 0', 'Rp 0'],
    [1500, 'Rp 1.500', 'Rp 1.500'],
    [950000, 'Rp 950.000', 'Rp 950.000'],
    [1000000, 'Rp 1.000.000', 'Rp 1 jt'],
    [9750000, 'Rp 9.750.000', 'Rp 9,8 jt'],
    [9840000, 'Rp 9.840.000', 'Rp 9,8 jt'],
    [10000000, 'Rp 10.000.000', 'Rp 10 jt'],
    [485000000, 'Rp 485.000.000', 'Rp 485 jt'],
    [999960000, 'Rp 999.960.000', 'Rp 1 M'],
    [1250000000, 'Rp 1.250.000.000', 'Rp 1,3 M'],
    [2500000000000, 'Rp 2.500.000.000.000', 'Rp 2.500 M'],
    [-1500, '-Rp 1.500', '-Rp 1.500'],
    [-9750000, '-Rp 9.750.000', '-Rp 9,8 jt'],
];

describe('rupiah formatting', () => {
    it.each(vectors)('formats %i', (amount, full, short) => {
        expect(formatRupiah(amount)).toBe(full);
        expect(formatRupiahShort(amount)).toBe(short);
    });

    it('rounds fractional input first', () => {
        expect(formatRupiah(485000000.4)).toBe('Rp 485.000.000');
    });
});
