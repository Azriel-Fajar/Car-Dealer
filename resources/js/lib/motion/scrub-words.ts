import { gsap, prefersReducedMotion, SplitText } from './core';

export function scrubWords(target: HTMLElement): SplitText | null {
    if (prefersReducedMotion()) {
        return null;
    }

    return SplitText.create(target, {
        type: 'words',
        wordsClass: 'split-word',
        autoSplit: true,
        onSplit: (self) =>
            gsap.fromTo(
                self.words,
                { opacity: 0.15 },
                {
                    opacity: 1,
                    stagger: 0.1,
                    ease: 'none',
                    scrollTrigger: {
                        trigger: target,
                        start: 'top 80%',
                        end: 'bottom 40%',
                        scrub: true,
                    },
                },
            ),
    });
}
