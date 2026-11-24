export type HotspotRect = {
    left: number;
    top: number;
    width: number;
    height: number;
};

export const MAX_HOTSPOTS = 8;

/** Clamp to 0–100 and round to one decimal (half up, like PHP round()). */
export const toPercent = (value: number): number =>
    Math.round(Math.min(Math.max(value, 0), 100) * 10) / 10;

// Scaling by 1000 before dividing keeps .x5 values exact for whole-pixel
// clicks (133 / 400 → 332.5 → 33.3) instead of a float like 33.2499….
const percentOf = (offset: number, size: number): number =>
    size > 0 ? Math.round((offset * 1000) / size) / 10 : 0;

/** Click position as a percentage of the rect, clamped 0–100, one decimal. */
export function pointFromClick(
    rect: HotspotRect,
    clientX: number,
    clientY: number,
): { x: number; y: number } {
    return {
        x: toPercent(percentOf(clientX - rect.left, rect.width)),
        y: toPercent(percentOf(clientY - rect.top, rect.height)),
    };
}
