import { router } from '@inertiajs/react';
import { useEffect, useLayoutEffect, useRef } from 'react';
import { gsap, prefersReducedMotion, ScrollTrigger } from './core';
import { getLenis, startLenis, stopLenis } from './lenis';
import { revealClip, revealFadeUp } from './reveal';

/** Smooth scrolling for the public site. Only PublicLayout calls this. */
export function useLenis(): void {
    useEffect(() => {
        startLenis();

        let frame = 0;
        const unsubscribe = router.on('navigate', () => {
            getLenis()?.scrollTo(0, { immediate: true, force: true });
            cancelAnimationFrame(frame);
            frame = requestAnimationFrame(() => ScrollTrigger.refresh());
        });

        return () => {
            unsubscribe();
            cancelAnimationFrame(frame);
            stopLenis();
        };
    }, []);
}

/**
 * Runs `setup` inside a gsap.context scoped to the returned ref, with a fresh
 * gsap.matchMedia(). Everything it creates is reverted on unmount or when
 * `deps` change. Layout effect, so `gsap.from` start states apply before paint.
 */
export function useGsapContext<T extends HTMLElement = HTMLDivElement>(
    setup: (tools: { mm: gsap.MatchMedia; reduced: boolean }) => void,
    deps: React.DependencyList = [],
): React.RefObject<T | null> {
    const ref = useRef<T>(null);

    useLayoutEffect(() => {
        const scope = ref.current ?? undefined;
        const mm = gsap.matchMedia(scope);
        const ctx = gsap.context(() => {
            setup({ mm, reduced: prefersReducedMotion() });
        }, scope);

        return () => {
            mm.revert();
            ctx.revert();
        };
    }, deps);

    return ref;
}

/** Reveals each `[data-reveal]` descendant; `data-reveal="clip"` uses a clip-path reveal. */
export function useReveal<
    T extends HTMLElement = HTMLDivElement,
>(): React.RefObject<T | null> {
    const ref = useGsapContext<T>(() => {
        ref.current
            ?.querySelectorAll<HTMLElement>('[data-reveal]')
            .forEach((element) => {
                if (element.dataset.reveal === 'clip') {
                    revealClip(element);
                } else {
                    revealFadeUp(element);
                }
            });
    });

    return ref;
}
