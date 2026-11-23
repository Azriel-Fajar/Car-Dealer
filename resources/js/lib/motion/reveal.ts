import { gsap, prefersReducedMotion } from './core';

export function revealFadeUp(
    targets: gsap.DOMTarget,
    options: { y?: number; stagger?: number; start?: string } = {},
): gsap.core.Tween {
    const { y = 32, stagger = 0.08, start = 'top 85%' } = options;
    const elements = gsap.utils.toArray<Element>(targets);
    const scrollTrigger = { trigger: elements[0], start, once: true };

    if (prefersReducedMotion()) {
        return gsap.from(elements, {
            opacity: 0,
            duration: 0.4,
            ease: 'none',
            scrollTrigger,
        });
    }

    return gsap.from(elements, {
        y,
        opacity: 0,
        stagger,
        duration: 0.9,
        ease: 'expo.out',
        scrollTrigger,
    });
}

export function revealClip(target: gsap.DOMTarget): gsap.core.Tween {
    const elements = gsap.utils.toArray<Element>(target);
    const scrollTrigger = {
        trigger: elements[0],
        start: 'top 85%',
        once: true,
    };

    if (prefersReducedMotion()) {
        return gsap.from(elements, {
            opacity: 0,
            duration: 0.4,
            ease: 'none',
            scrollTrigger,
        });
    }

    return gsap.fromTo(
        elements,
        { clipPath: 'inset(12%)', scale: 1.08 },
        {
            clipPath: 'inset(0%)',
            scale: 1,
            duration: 1.2,
            ease: 'expo.out',
            scrollTrigger,
        },
    );
}
