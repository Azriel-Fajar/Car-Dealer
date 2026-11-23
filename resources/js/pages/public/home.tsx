import { Head } from '@inertiajs/react';
import { useRef } from 'react';
import { Button } from '@/components/ui/button';
import { formatRupiah, formatRupiahShort } from '@/lib/format';
import { DESKTOP_QUERY, gsap } from '@/lib/motion/core';
import { useGsapContext, useReveal } from '@/lib/motion/hooks';
import { getLenis } from '@/lib/motion/lenis';
import { parallax } from '@/lib/motion/parallax';
import { revealFadeUp } from '@/lib/motion/reveal';
import { scrubWords } from '@/lib/motion/scrub-words';
import { splitLines } from '@/lib/motion/split-lines';

const highlights = [
    { index: '01', tag: 'Pick of the Week' },
    { index: '02', tag: null },
    { index: '03', tag: null },
];

const specRows = ['0–100', 'Power', 'Engine'];

const services = [
    {
        index: '01',
        title: 'Jual Beli',
        body: 'Mobil baru dan bekas terkurasi, lengkap dengan riwayat servis dan inspeksi menyeluruh sebelum sampai di tangan Anda.',
    },
    {
        index: '02',
        title: 'Kredit',
        body: 'Simulasi cicilan transparan bersama mitra leasing tepercaya. Tanpa biaya tersembunyi, tanpa kejutan di akhir.',
    },
    {
        index: '03',
        title: 'Servis & Trade-in',
        body: 'Tukar tambah mobil lama Anda dengan penilaian yang jujur, lalu rawat unit berikutnya di bengkel kami.',
    },
];

function scrollToHighlights(event: React.MouseEvent<HTMLAnchorElement>) {
    const lenis = getLenis();

    if (!lenis) {
        return;
    }

    event.preventDefault();
    lenis.scrollTo('#sorotan');
}

function SectionLabel({ children }: { children: React.ReactNode }) {
    return (
        <p className="flex items-center gap-3 label-spec text-muted-foreground">
            <span aria-hidden="true" className="h-px w-8 bg-ember" />
            {children}
        </p>
    );
}

function HighlightCard({ index, tag }: { index: string; tag: string | null }) {
    return (
        <article className="flex w-[80vw] shrink-0 snap-start flex-col overflow-hidden rounded-lg border border-border bg-card sm:w-[60vw] lg:h-[min(56svh,500px)] lg:w-[min(68vw,920px)] lg:flex-row">
            <div
                aria-hidden="true"
                className="relative isolate aspect-16/10 overflow-hidden bg-secondary lg:aspect-auto lg:w-[56%]"
            >
                <div className="absolute -right-1/4 -bottom-1/2 -z-10 size-[110%] rounded-full bg-ember/20 blur-[90px]" />
                <div className="absolute inset-x-0 top-[62%] h-px bg-linear-to-r from-transparent via-ember/40 to-transparent" />
                <span className="absolute -bottom-[0.18em] -left-[0.04em] font-display text-[clamp(7rem,18vw,15rem)] leading-none font-extrabold text-foreground/5">
                    {index}
                </span>
                <span className="absolute right-5 bottom-5 label-spec text-muted-foreground">
                    Foto unit menyusul
                </span>
            </div>

            <div className="flex flex-1 flex-col justify-between gap-8 p-6 lg:p-8">
                <div>
                    <p className="label-spec text-ember">
                        {index}
                        {tag ? ` ${tag}` : null}
                    </p>
                    <h3 className="mt-3 font-display text-3xl leading-none font-extrabold uppercase lg:text-4xl">
                        Unit Sorotan {index}
                    </h3>
                </div>

                <dl className="divide-y divide-border border-y border-border">
                    {specRows.map((label) => (
                        <div
                            key={label}
                            className="flex items-baseline justify-between py-2.5"
                        >
                            <dt className="label-spec text-muted-foreground">
                                {label}
                            </dt>
                            <dd className="font-mono text-sm">—</dd>
                        </div>
                    ))}
                </dl>

                <div>
                    <p className="label-spec text-muted-foreground">
                        Contoh harga
                    </p>
                    <p className="mt-2 font-display text-3xl leading-none font-extrabold lg:text-4xl">
                        {formatRupiah(485000000)}
                    </p>
                    <p className="mt-2 font-mono text-sm text-muted-foreground">
                        {formatRupiahShort(9800000) + ' / bln'}
                    </p>
                </div>
            </div>
        </article>
    );
}

function Services() {
    const ref = useReveal<HTMLElement>();

    return (
        <section
            ref={ref}
            aria-labelledby="layanan-judul"
            className="mx-auto max-w-screen-2xl px-5 py-24 md:px-10 md:py-40"
        >
            <div data-reveal>
                <SectionLabel>03 — Layanan</SectionLabel>
                <h2
                    id="layanan-judul"
                    className="mt-6 max-w-[14ch] font-display text-display-lg font-extrabold uppercase"
                >
                    Satu Tempat, Semua Urusan
                </h2>
            </div>

            <ol className="mt-14 border-t border-border md:mt-20">
                {services.map((service) => (
                    <li
                        key={service.index}
                        data-reveal
                        className="grid gap-3 border-b border-border py-9 md:grid-cols-[6rem_minmax(0,1fr)_minmax(0,26rem)] md:items-baseline md:gap-8 md:py-14"
                    >
                        <span className="font-mono text-sm text-ember">
                            {service.index}
                        </span>
                        <h3 className="font-display text-4xl leading-none font-extrabold uppercase md:text-6xl">
                            {service.title}
                        </h3>
                        <p className="text-base leading-relaxed text-muted-foreground">
                            {service.body}
                        </p>
                    </li>
                ))}
            </ol>
        </section>
    );
}

export default function Home() {
    const titleRef = useRef<HTMLHeadingElement>(null);
    const heroBackdropRef = useRef<HTMLDivElement>(null);
    const statementRef = useRef<HTMLParagraphElement>(null);
    const highlightsRef = useRef<HTMLElement>(null);
    const viewportRef = useRef<HTMLDivElement>(null);
    const trackRef = useRef<HTMLDivElement>(null);

    const root = useGsapContext<HTMLDivElement>(({ mm }) => {
        if (titleRef.current) {
            splitLines(titleRef.current);
        }

        if (heroBackdropRef.current) {
            parallax(heroBackdropRef.current);
        }

        revealFadeUp('[data-hero-fade]', { stagger: 0.12 });

        if (statementRef.current) {
            scrubWords(statementRef.current);
        }

        mm.add(
            `${DESKTOP_QUERY} and (prefers-reduced-motion: no-preference)`,
            () => {
                const section = highlightsRef.current;
                const viewport = viewportRef.current;
                const track = trackRef.current;

                if (!section || !viewport || !track) {
                    return;
                }

                const distance = () =>
                    Math.max(0, track.scrollWidth - viewport.clientWidth);

                viewport.scrollLeft = 0;
                gsap.set(viewport, { overflow: 'hidden' });
                gsap.to(track, {
                    x: () => -distance(),
                    ease: 'none',
                    scrollTrigger: {
                        trigger: section,
                        start: 'top top',
                        end: () => `+=${distance()}`,
                        pin: true,
                        scrub: 1,
                        invalidateOnRefresh: true,
                    },
                });
            },
        );
    });

    return (
        <div ref={root}>
            <Head title="Beranda" />

            <section className="relative isolate flex min-h-svh items-end overflow-hidden">
                <div aria-hidden="true" className="absolute inset-0 -z-10">
                    <div
                        ref={heroBackdropRef}
                        className="absolute inset-x-0 top-0 h-[120%]"
                    >
                        <div className="absolute inset-0 bg-linear-to-b from-card via-background to-background" />
                        <div className="absolute -top-[20vh] -right-[25vw] size-[90vw] max-w-[1100px] rounded-full bg-ember/25 blur-[140px] md:-right-[10vw] md:size-[60vw]" />
                        <div className="absolute bottom-[32%] -left-[20vw] size-[50vw] rounded-full bg-ember/10 blur-[120px]" />
                        <div className="absolute inset-x-0 bottom-[35%] h-px bg-linear-to-r from-transparent via-ember/50 to-transparent" />
                    </div>
                    <div className="absolute inset-x-0 bottom-0 h-1/3 bg-linear-to-t from-background to-transparent" />
                </div>

                <div className="mx-auto w-full max-w-screen-2xl px-5 pt-32 pb-16 md:px-10 md:pb-24">
                    <div data-hero-fade>
                        <SectionLabel>01 — Pemecut Motors</SectionLabel>
                    </div>
                    <h1
                        ref={titleRef}
                        className="mt-6 max-w-[12ch] font-display text-display-xl font-extrabold text-balance uppercase"
                    >
                        Temukan Mobil Impianmu
                    </h1>
                    <div
                        data-hero-fade
                        className="mt-10 flex flex-col gap-8 md:flex-row md:items-end md:justify-between"
                    >
                        <p className="max-w-md text-base leading-relaxed text-muted-foreground md:text-lg">
                            Mobil baru &amp; bekas terkurasi, simulasi kredit
                            transparan, dan test drive tanpa ribet.
                        </p>
                        <Button
                            asChild
                            size="lg"
                            className="h-12 w-fit rounded-full px-7 text-base font-semibold"
                        >
                            <a href="#sorotan" onClick={scrollToHighlights}>
                                Cari Mobil Impianmu ↗
                            </a>
                        </Button>
                    </div>
                </div>
            </section>

            <section
                aria-label="Filosofi"
                className="mx-auto max-w-screen-2xl px-5 py-28 md:px-10 md:py-44"
            >
                <SectionLabel>Cara kami bekerja</SectionLabel>
                <p
                    ref={statementRef}
                    className="mt-8 max-w-[22ch] font-display text-display-lg font-extrabold text-balance uppercase"
                >
                    Setiap unit kami pilih dengan teliti, supaya Anda cukup
                    memikirkan satu hal: ke mana perjalanan berikutnya.
                </p>
            </section>

            <section
                ref={highlightsRef}
                id="sorotan"
                aria-labelledby="sorotan-judul"
                className="bg-background lg:flex lg:min-h-svh lg:flex-col lg:justify-center"
            >
                <div className="mx-auto w-full max-w-screen-2xl px-5 py-20 md:px-10 lg:pt-28 lg:pb-12">
                    <div className="flex items-end justify-between gap-6">
                        <div>
                            <SectionLabel>02 — Sorotan</SectionLabel>
                            <h2
                                id="sorotan-judul"
                                className="mt-6 font-display text-display-lg font-extrabold uppercase"
                            >
                                Sorotan Minggu Ini
                            </h2>
                        </div>
                        <p
                            aria-hidden="true"
                            className="shrink-0 label-spec text-muted-foreground lg:hidden"
                        >
                            Geser →
                        </p>
                    </div>

                    <div
                        ref={viewportRef}
                        className="mt-10 snap-x snap-mandatory overflow-x-auto overscroll-x-contain pb-4 lg:mt-12"
                    >
                        <div
                            ref={trackRef}
                            className="flex w-max gap-4 md:gap-6"
                        >
                            {highlights.map((card) => (
                                <HighlightCard key={card.index} {...card} />
                            ))}
                        </div>
                    </div>
                </div>
            </section>

            <Services />
        </div>
    );
}
