import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, CalendarClock, Receipt } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { home } from '@/routes';

function AccountCard({
    label,
    icon: Icon,
    empty,
    children,
}: {
    label: string;
    icon: LucideIcon;
    empty: string;
    children?: React.ReactNode;
}) {
    return (
        <section className="flex flex-col rounded-2xl border border-border bg-card p-6 md:p-8">
            <div className="flex items-center justify-between gap-4">
                <h2 className="label-spec text-muted-foreground">{label}</h2>
                <Icon aria-hidden="true" className="size-4 text-ember" />
            </div>
            <div className="flex flex-1 flex-col items-start justify-end gap-5 pt-12 md:pt-16">
                <p className="text-lg text-foreground">{empty}</p>
                {children}
            </div>
        </section>
    );
}

export default function AccountIndex() {
    const { auth } = usePage().props;
    const firstName = auth.user.name.split(' ')[0];

    return (
        <>
            <Head title="Akun Saya" />

            <header className="mb-10 md:mb-14">
                <p className="mb-4 label-spec text-ember">Akun Saya</p>
                <h1 className="font-display text-5xl leading-[0.9] font-extrabold tracking-tight uppercase md:text-7xl">
                    Halo, {firstName}
                </h1>
                <p className="mt-4 max-w-xl text-base text-muted-foreground md:text-lg">
                    Pantau pesanan dan jadwal test drive Anda di sini.
                </p>
            </header>

            <div className="grid gap-4 md:grid-cols-2 md:gap-6">
                <AccountCard
                    label="Pesanan aktif"
                    icon={Receipt}
                    empty="Belum ada pesanan."
                >
                    <Button asChild className="rounded-full px-5 font-semibold">
                        <Link href={home()}>
                            Jelajahi mobil
                            <ArrowRight aria-hidden="true" />
                        </Link>
                    </Button>
                </AccountCard>
                <AccountCard
                    label="Test drive terdekat"
                    icon={CalendarClock}
                    empty="Belum ada jadwal test drive."
                />
            </div>
        </>
    );
}
