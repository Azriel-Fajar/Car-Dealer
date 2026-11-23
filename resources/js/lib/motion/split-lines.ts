import { gsap, prefersReducedMotion, SplitText } from './core';

export function splitLines(
    target: HTMLElement,
    options: { stagger?: number; start?: string } = {},
): SplitText | null {
    const { stagger = 0.08, start = 'top 85%' } = options;

    if (prefersReducedMotion()) {
        gsap.from(target, { opacity: 0, duration: 0.4, ease: 'none' });

        return null;
    }

    return SplitText.create(target, {
        type: 'lines',
        mask: 'lines',
        linesClass: 'split-line',
        autoSplit: true,
        onSplit: (self) =>
            gsap.from(self.lines, {
                yPercent: 100,
                stagger,
                duration: 0.9,
                ease: 'expo.out',
                scrollTrigger: { trigger: target, start, once: true },
            }),
    });
}
