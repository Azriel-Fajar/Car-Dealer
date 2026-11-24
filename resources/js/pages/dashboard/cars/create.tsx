import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import CarController from '@/actions/App/Http/Controllers/Dashboard/CarController';
import { CarForm } from '@/components/dashboard/cars/car-form';
import type { CarFormOptions, CarTab } from '@/components/dashboard/cars/types';

export default function CarsCreate({
    options,
    tab,
}: {
    options: CarFormOptions;
    tab: CarTab;
}) {
    return (
        <>
            <Head title="Tambah mobil" />
            <div className="flex min-w-0 flex-1 flex-col gap-6 p-4 md:p-6">
                <header className="space-y-2">
                    <Link
                        href={CarController.index()}
                        className="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" />
                        Daftar mobil
                    </Link>
                    <h1 className="font-display text-3xl leading-none font-extrabold tracking-tight uppercase md:text-4xl">
                        Tambah mobil
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Isi info & spesifikasi dulu. Warna, opsi, foto, dan
                        hotspot bisa ditambahkan setelah mobil tersimpan.
                    </p>
                </header>

                <CarForm options={options} tab={tab} />
            </div>
        </>
    );
}

CarsCreate.layout = {
    breadcrumbs: [
        { title: 'Mobil', href: CarController.index() },
        { title: 'Tambah', href: CarController.create() },
    ],
};
