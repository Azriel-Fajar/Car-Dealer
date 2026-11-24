import { Head, Link, router } from '@inertiajs/react';
import {
    CarFront,
    LayoutGrid,
    List,
    Pencil,
    Plus,
    RotateCcw,
    Star,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';
import CarController from '@/actions/App/Http/Controllers/Dashboard/CarController';
import { carStatusTone } from '@/components/dashboard/cars/status';
import type {
    EnumOption,
    NamedOption,
} from '@/components/dashboard/cars/types';
import { ConfirmDeleteDialog } from '@/components/dashboard/confirm-delete-dialog';
import { DataTableShell } from '@/components/dashboard/data-table-shell';
import { DataToolbar } from '@/components/dashboard/data-toolbar';
import { EmptyState } from '@/components/dashboard/empty-state';
import { PaginationBar } from '@/components/dashboard/pagination-bar';
import { RowActions } from '@/components/dashboard/row-actions';
import type { RowAction } from '@/components/dashboard/row-actions';
import { StatusPill } from '@/components/dashboard/status-pill';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { formatRupiah } from '@/lib/format';
import { cleanQuery } from '@/lib/query';
import { cn } from '@/lib/utils';
import type { IndexFilters, Paginated } from '@/types';

type CarRow = {
    id: number;
    name: string;
    slug: string;
    year: number;
    brand_name: string;
    category_name: string;
    base_price: number;
    stock: number;
    status: string;
    status_label: string;
    badge_label: string | null;
    is_featured: boolean;
    cover_url: string | null;
    is_trashed: boolean;
};

type Query = {
    q?: string;
    brand?: string;
    category?: string;
    status?: string;
    trashed?: string;
    view?: string;
};

const ALL = 'all';

export default function CarsIndex({
    items,
    filters,
    options,
}: {
    items: Paginated<CarRow>;
    filters: IndexFilters;
    options: {
        brands: NamedOption[];
        categories: NamedOption[];
        statuses: EnumOption[];
    };
}) {
    // The target outlives the open flag so the dialog keeps its content while closing.
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [deleting, setDeleting] = useState<CarRow | null>(null);
    const [deleteProcessing, setDeleteProcessing] = useState(false);

    const current: Required<Query> = {
        q: filters.q ?? '',
        brand: filters.brand ?? '',
        category: filters.category ?? '',
        status: filters.status ?? '',
        trashed: filters.trashed ?? '',
        view: filters.view === 'cards' ? 'cards' : 'list',
    };
    const trashed = current.trashed === '1';
    const cards = current.view === 'cards';

    const visit = (next: Query) => {
        const query = { ...current, ...next };

        router.get(
            CarController.index.url(),
            cleanQuery({
                ...query,
                view: query.view === 'cards' ? 'cards' : '',
            }),
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const openDelete = (car: CarRow) => {
        setDeleting(car);
        setDeleteOpen(true);
    };

    const handleDelete = () => {
        if (!deleting) {
            return;
        }

        router.delete(CarController.destroy.url(deleting), {
            preserveScroll: true,
            onStart: () => setDeleteProcessing(true),
            onFinish: () => {
                setDeleteProcessing(false);
                setDeleteOpen(false);
            },
        });
    };

    const handleRestore = (car: CarRow) => {
        router.patch(
            CarController.restore.url(car),
            {},
            { preserveScroll: true },
        );
    };

    const actionsFor = (car: CarRow): RowAction[] =>
        car.is_trashed
            ? [
                  {
                      label: 'Pulihkan',
                      icon: RotateCcw,
                      onSelect: () => handleRestore(car),
                  },
              ]
            : [
                  {
                      label: 'Edit',
                      icon: Pencil,
                      onSelect: () => router.visit(CarController.edit.url(car)),
                  },
                  {
                      label: 'Hapus',
                      icon: Trash2,
                      destructive: true,
                      onSelect: () => openDelete(car),
                  },
              ];

    const filterSelect = (
        key: 'brand' | 'category' | 'status',
        label: string,
        allLabel: string,
        choices: { value: string; label: string }[],
    ) => (
        <Select
            value={current[key] || ALL}
            onValueChange={(value) =>
                visit({ [key]: value === ALL ? '' : value })
            }
        >
            <SelectTrigger
                aria-label={label}
                className="h-10 w-[calc(50%-0.25rem)] rounded-xl bg-card sm:w-40"
            >
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value={ALL}>{allLabel}</SelectItem>
                {choices.map((choice) => (
                    <SelectItem key={choice.value} value={choice.value}>
                        {choice.label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );

    const toOptions = (list: NamedOption[]) =>
        list.map((item) => ({ value: String(item.id), label: item.name }));

    const filterControls = (
        <>
            {filterSelect(
                'brand',
                'Filter brand',
                'Semua brand',
                toOptions(options.brands),
            )}
            {filterSelect(
                'category',
                'Filter kategori',
                'Semua kategori',
                toOptions(options.categories),
            )}
            {filterSelect(
                'status',
                'Filter status',
                'Semua status',
                options.statuses,
            )}
            <Button
                type="button"
                variant={trashed ? 'default' : 'outline'}
                aria-pressed={trashed}
                className="h-10 w-[calc(50%-0.25rem)] rounded-xl sm:w-auto"
                onClick={() => visit({ trashed: trashed ? '' : '1' })}
            >
                <Trash2 />
                Terhapus
            </Button>
        </>
    );

    const toolbarActions = (
        <>
            <ToggleGroup
                type="single"
                variant="outline"
                value={current.view}
                onValueChange={(value) => value && visit({ view: value })}
                aria-label="Tampilan"
                className="h-10 shrink-0 rounded-xl bg-card"
            >
                <ToggleGroupItem
                    value="list"
                    aria-label="Tampilan list"
                    className="h-10 rounded-l-xl px-3"
                >
                    <List />
                </ToggleGroupItem>
                <ToggleGroupItem
                    value="cards"
                    aria-label="Tampilan kartu"
                    className="h-10 rounded-r-xl px-3"
                >
                    <LayoutGrid />
                </ToggleGroupItem>
            </ToggleGroup>
            <Button asChild className="h-10 flex-1 rounded-xl sm:flex-none">
                <Link href={CarController.create()}>
                    <Plus />
                    Tambah mobil
                </Link>
            </Button>
        </>
    );

    const filtered =
        current.q !== '' ||
        current.brand !== '' ||
        current.category !== '' ||
        current.status !== '';

    const empty = (
        <EmptyState
            icon={CarFront}
            title={
                trashed
                    ? 'Tidak ada mobil terhapus'
                    : filtered
                      ? 'Mobil tidak ditemukan'
                      : 'Belum ada mobil'
            }
            description={
                trashed
                    ? 'Mobil yang dihapus akan muncul di sini dan bisa dipulihkan.'
                    : filtered
                      ? 'Tidak ada mobil yang cocok dengan pencarian atau filter ini.'
                      : 'Tambahkan mobil pertama untuk mulai mengisi katalog.'
            }
        />
    );

    return (
        <>
            <Head title="Mobil" />
            <div className="flex min-w-0 flex-1 flex-col gap-6 p-4 md:p-6">
                <header className="space-y-2">
                    <p className="label-spec text-muted-foreground">Katalog</p>
                    <h1 className="font-display text-3xl leading-none font-extrabold tracking-tight uppercase md:text-4xl">
                        {trashed ? 'Mobil terhapus' : 'Mobil'}
                    </h1>
                </header>

                <DataToolbar
                    search={current.q}
                    onSearch={(q) => visit({ q })}
                    filters={filterControls}
                    actions={toolbarActions}
                />

                {cards ? (
                    items.data.length === 0 ? (
                        <DataTableShell>{empty}</DataTableShell>
                    ) : (
                        <ul className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4">
                            {items.data.map((car) => (
                                <li
                                    key={car.id}
                                    className="flex flex-col overflow-hidden rounded-2xl border border-border bg-card text-card-foreground"
                                >
                                    <div className="relative">
                                        <CarCover
                                            car={car}
                                            className="aspect-[16/10] w-full rounded-none border-0"
                                        />
                                        {car.badge_label && (
                                            <span className="absolute top-3 left-3 rounded-full bg-foreground px-2.5 py-0.5 label-spec text-background">
                                                {car.badge_label}
                                            </span>
                                        )}
                                        {car.is_featured && (
                                            <FeaturedStar className="absolute top-3 right-3 rounded-full bg-card/90 p-1" />
                                        )}
                                    </div>
                                    <div className="flex flex-1 flex-col gap-3 p-4">
                                        <div className="flex items-start justify-between gap-2">
                                            <div className="min-w-0">
                                                <p className="truncate font-semibold">
                                                    {car.name}
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    {car.brand_name} ·{' '}
                                                    {car.year}
                                                </p>
                                            </div>
                                            <RowActions
                                                items={actionsFor(car)}
                                            />
                                        </div>
                                        <div className="mt-auto flex flex-wrap items-center justify-between gap-2">
                                            <p className="font-semibold tabular-nums">
                                                {formatRupiah(car.base_price)}
                                            </p>
                                            <StatusPill
                                                tone={carStatusTone(car.status)}
                                            >
                                                {car.status_label}
                                            </StatusPill>
                                        </div>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )
                ) : (
                    <DataTableShell>
                        {items.data.length === 0 ? (
                            empty
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow className="hover:bg-transparent">
                                        <TableHead className="w-20 pl-4 label-spec text-muted-foreground">
                                            Foto
                                        </TableHead>
                                        <TableHead className="label-spec text-muted-foreground">
                                            Mobil
                                        </TableHead>
                                        <TableHead className="label-spec text-muted-foreground">
                                            Kategori
                                        </TableHead>
                                        <TableHead className="text-right label-spec text-muted-foreground">
                                            Harga
                                        </TableHead>
                                        <TableHead className="text-right label-spec text-muted-foreground">
                                            Stok
                                        </TableHead>
                                        <TableHead className="label-spec text-muted-foreground">
                                            Status
                                        </TableHead>
                                        <TableHead className="w-12 text-center label-spec text-muted-foreground">
                                            <span className="sr-only">
                                                Unggulan
                                            </span>
                                            <Star
                                                aria-hidden="true"
                                                className="mx-auto size-3.5"
                                            />
                                        </TableHead>
                                        <TableHead className="w-12 pr-4">
                                            <span className="sr-only">
                                                Aksi
                                            </span>
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {items.data.map((car) => (
                                        <TableRow key={car.id}>
                                            <TableCell className="pl-4">
                                                <CarCover
                                                    car={car}
                                                    className="h-10 w-16"
                                                />
                                            </TableCell>
                                            <TableCell>
                                                <p className="font-medium">
                                                    {car.name}
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    {car.brand_name} ·{' '}
                                                    {car.year}
                                                </p>
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {car.category_name}
                                            </TableCell>
                                            <TableCell className="text-right whitespace-nowrap tabular-nums">
                                                {formatRupiah(car.base_price)}
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                {car.stock}
                                            </TableCell>
                                            <TableCell>
                                                <StatusPill
                                                    tone={carStatusTone(
                                                        car.status,
                                                    )}
                                                >
                                                    {car.status_label}
                                                </StatusPill>
                                            </TableCell>
                                            <TableCell className="text-center">
                                                {car.is_featured ? (
                                                    <FeaturedStar className="inline-flex" />
                                                ) : (
                                                    <span className="text-muted-foreground">
                                                        -
                                                    </span>
                                                )}
                                            </TableCell>
                                            <TableCell className="pr-4 text-right">
                                                <RowActions
                                                    items={actionsFor(car)}
                                                />
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </DataTableShell>
                )}

                <PaginationBar meta={items} />
            </div>

            <ConfirmDeleteDialog
                open={deleteOpen}
                onOpenChange={setDeleteOpen}
                title={`Hapus mobil ${deleting?.name ?? ''}?`}
                description="Mobil disembunyikan dari katalog dan dashboard. Anda bisa memulihkannya dari filter Terhapus."
                onConfirm={handleDelete}
                processing={deleteProcessing}
            />
        </>
    );
}

function CarCover({ car, className }: { car: CarRow; className?: string }) {
    return (
        <span
            className={cn(
                'flex items-center justify-center overflow-hidden rounded-lg border border-border bg-muted text-muted-foreground',
                className,
            )}
        >
            {car.cover_url ? (
                <img
                    src={car.cover_url}
                    alt={car.name}
                    loading="lazy"
                    className="size-full object-cover"
                />
            ) : (
                <CarFront aria-hidden="true" className="size-5" />
            )}
        </span>
    );
}

function FeaturedStar({ className }: { className?: string }) {
    return (
        <span className={className} title="Mobil unggulan">
            <Star
                aria-label="Mobil unggulan"
                className="size-4 fill-amber-400 text-amber-500"
            />
        </span>
    );
}

CarsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Mobil',
            href: CarController.index(),
        },
    ],
};
