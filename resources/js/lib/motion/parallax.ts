import { gsap, prefersReducedMotion } from './core';

export function parallax(
    target: HTMLElement,
    options: { distance?: number } = {},
): gsap.core.Tween | null {
    const { distance = 15 } = options;

    if (prefersReducedMotion()) {
        return null;
    }

    return gsap.to(target, {
        yPercent: -distance,
        ease: 'none',
        scrollTrigger: {
            trigger: target,
            // clamp() keeps a layer that starts in view (the hero) at progress 0 on load.
            start: 'clamp(top bottom)',
            end: 'bottom top',
            scrub: true,
        },
    });
}
