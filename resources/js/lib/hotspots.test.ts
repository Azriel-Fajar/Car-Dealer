import { describe, expect, it } from 'vite-plus/test';
import { pointFromClick } from './hotspots';

const rect = { left: 100, top: 50, width: 400, height: 200 };

describe('pointFromClick', () => {
    it('returns the click point as a percentage of the rect', () => {
        expect(pointFromClick(rect, 300, 150)).toEqual({ x: 50, y: 50 });
    });

    it('clamps clicks outside the rect to 0–100', () => {
        expect(pointFromClick(rect, 600, 10)).toEqual({ x: 100, y: 0 });
    });

    it('rounds to one decimal', () => {
        expect(pointFromClick(rect, 233, 117)).toEqual({ x: 33.3, y: 33.5 });
    });
});
