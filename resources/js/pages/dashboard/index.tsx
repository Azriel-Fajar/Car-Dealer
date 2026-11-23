import { Head, usePage } from '@inertiajs/react';
import { BarChart3, Receipt } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { roleLabels } from '@/lib/roles';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';

const kpis: { label: string; accent?: boolean }[] = [
    { label: 'Revenue booking · bulan ini', accent: true },
    { label: 'Pesanan' },
    { label: 'Menunggu verifikasi' },
    { label: 'Test drive hari ini' },
];

function KpiCard({
    label,
    accent = false,
}: {
    label: string;
    accent?: boolean;
}) {
    return (
        <div
            className={cn(
                'flex min-h-40 flex-col justify-between gap-6 rounded-2xl border p-5 md:p-6',
                accent
                    ? 'border-transparent bg-primary text-primary-foreground'
                    : 'border-border bg-card text-card-foreground',
            )}
        >
            <div className="flex items-start justify-between gap-3">
                <p
                    className={cn(
                        'label-spec',
                        accent
                            ? 'text-primary-foreground/70'
                            : 'text-muted-foreground',
                    )}
                >
                    {label}
                </p>
                {accent && (
                    <span
                        aria-hidden="true"
                        className="mt-1 size-2 shrink-0 rotate-45 bg-ember"
                    />
                )}
            </div>
            <div>
                <p className="font-display text-5xl leading-none font-extrabold">
                    —
                </p>
                <p
                    className={cn(
                        'mt-2 text-sm',
                        accent
                            ? 'text-primary-foreground/70'
                            : 'text-muted-foreground',
                    )}
                >
                    Belum ada data
                </p>
            </div>
        </div>
    );
}

function Panel({
    title,
    icon: Icon,
    empty,
    className,
}: {
    title: string;
    icon: LucideIcon;
    empty: string;
    className?: string;
}) {
    return (
        <section
            className={cn(
                'flex min-h-72 flex-col rounded-2xl border border-border bg-card p-5 text-card-foreground md:p-6',
                className,
            )}
        >
            <h2 className="text-base font-semibold">{title}</h2>
            <div className="flex flex-1 flex-col items-center justify-center gap-3 py-10 text-center">
                <span className="flex size-11 items-center justify-center rounded-xl bg-muted text-muted-foreground">
                    <Icon aria-hidden="true" className="size-5" />
                </span>
                <p className="text-sm text-muted-foreground">{empty}</p>
            </div>
        </section>
    );
}

export default function Dashboard() {
    const { auth } = usePage().props;
    const firstName = auth.user.name.split(' ')[0];

    return (
        <>
            <Head title="Ringkasan" />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <header className="flex flex-wrap items-center gap-3">
                    <h1 className="font-display text-3xl leading-none font-extrabold tracking-tight uppercase md:text-4xl">
                        Selamat datang, {firstName}
                    </h1>
                    <span className="rounded-full border border-border bg-card px-3 py-1 label-spec text-muted-foreground">
                        {roleLabels[auth.user.role]}
                    </span>
                </header>

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {kpis.map((kpi) => (
                        <KpiCard key={kpi.label} {...kpi} />
                    ))}
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    <Panel
                        title="Penjualan per bulan"
                        icon={BarChart3}
                        empty="Belum ada data penjualan."
                        className="lg:col-span-2"
                    />
                    <Panel
                        title="Pesanan terbaru"
                        icon={Receipt}
                        empty="Belum ada pesanan."
                    />
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Ringkasan',
            href: dashboard(),
        },
    ],
};
