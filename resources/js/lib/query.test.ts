import { describe, expect, it } from 'vite-plus/test';
import { cleanQuery } from './query';

describe('cleanQuery', () => {
    it('drops empty values and stringifies the rest', () => {
        expect(
            cleanQuery({ q: '', brand: 3, trashed: true, status: null }),
        ).toEqual({ brand: '3', trashed: '1' });
    });

    it('drops undefined and false, keeps zero and text', () => {
        expect(
            cleanQuery({ q: 'toy', page: 0, active: false, role: undefined }),
        ).toEqual({ q: 'toy', page: '0' });
    });
});
