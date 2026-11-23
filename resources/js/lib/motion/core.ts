import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { SplitText } from 'gsap/SplitText';

gsap.registerPlugin(ScrollTrigger, SplitText);

export { gsap, ScrollTrigger, SplitText };

export const DESKTOP_QUERY = '(min-width: 1024px)';
export const REDUCED_MOTION_QUERY = '(prefers-reduced-motion: reduce)';

export function prefersReducedMotion(): boolean {
    if (typeof window === 'undefined') {
        return false;
    }

    return window.matchMedia(REDUCED_MOTION_QUERY).matches;
}
