import Lenis from 'lenis';
import 'lenis/dist/lenis.css';
import { gsap, prefersReducedMotion, ScrollTrigger } from './core';

let lenis: Lenis | null = null;

function tick(time: number): void {
    lenis?.raf(time * 1000);
}

export function startLenis(): Lenis | null {
    if (prefersReducedMotion()) {
        return null;
    }

    if (lenis) {
        return lenis;
    }

    lenis = new Lenis({ lerp: 0.1, autoRaf: false });
    lenis.on('scroll', () => ScrollTrigger.update());
    gsap.ticker.add(tick);
    gsap.ticker.lagSmoothing(0);

    return lenis;
}

export function stopLenis(): void {
    if (!lenis) {
        return;
    }

    gsap.ticker.remove(tick);
    lenis.destroy();
    lenis = null;
    gsap.ticker.lagSmoothing(500, 33);
}

export function getLenis(): Lenis | null {
    return lenis;
}
