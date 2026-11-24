import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import CarController from '@/actions/App/Http/Controllers/Dashboard/CarController';
import { CarForm } from '@/components/dashboard/cars/car-form';
import { carStatusTone } from '@/components/dashboard/cars/status';
import type {
    CarDetail,
    CarFormOptions,
    CarTab,
} from '@/components/dashboard/cars/types';
import { StatusPill } from '@/components/dashboard/status-pill';

export default function CarsEdit({
    car,
    options,
    tab,
}: {
    car: CarDetail;
    options: CarFormOptions;
    tab: CarTab;
}) {
    const statusLabel =
        options.statuses.find((status) => status.value === car.status)?.label ??
        car.status;

    return (
        <>
            <Head title={`Ubah ${car.name}`} />
            <div className="flex min-w-0 flex-1 flex-col gap-6 p-4 md:p-6">
                <header className="space-y-2">
                    <Link
                        href={CarController.index()}
                        className="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" />
                        Daftar mobil
                    </Link>
                    <div className="flex flex-wrap items-center gap-3">
                        <h1 className="min-w-0 font-display text-3xl leading-none font-extrabold tracking-tight break-words uppercase md:text-4xl">
                            {car.name}
                        </h1>
                        <StatusPill tone={carStatusTone(car.status)}>
                            {statusLabel}
                        </StatusPill>
                    </div>
                    <p className="text-sm break-all text-muted-foreground">
                        {car.brand.name} · {car.year} ·{' '}
                        <span className="font-mono">{car.slug}</span>
                    </p>
                </header>

                <CarForm car={car} options={options} tab={tab} />
            </div>
        </>
    );
}

CarsEdit.layout = {
    breadcrumbs: [{ title: 'Mobil', href: CarController.index() }],
};
