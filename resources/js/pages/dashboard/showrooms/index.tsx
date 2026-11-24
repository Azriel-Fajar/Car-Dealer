import { Form, Head, router } from '@inertiajs/react';
import { Pencil, Plus, Store, Trash2 } from 'lucide-react';
import { useState } from 'react';
import ShowroomController from '@/actions/App/Http/Controllers/Dashboard/ShowroomController';
import { ConfirmDeleteDialog } from '@/components/dashboard/confirm-delete-dialog';
import { DataTableShell } from '@/components/dashboard/data-table-shell';
import { DataToolbar } from '@/components/dashboard/data-toolbar';
import { EmptyState } from '@/components/dashboard/empty-state';
import { PaginationBar } from '@/components/dashboard/pagination-bar';
import { RowActions } from '@/components/dashboard/row-actions';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { cleanQuery } from '@/lib/query';
import type { IndexFilters, Paginated } from '@/types';

type ShowroomRow = {
    id: number;
    name: string;
    city: string;
    address: string;
    phone: string;
    lat: string;
    lng: string;
    cars_count: number;
};

export default function ShowroomsIndex({
    items,
    filters,
}: {
    items: Paginated<ShowroomRow>;
    filters: IndexFilters;
}) {
    // Targets outlive the open flags so dialogs keep their content while closing.
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<ShowroomRow | null>(null);
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [deleting, setDeleting] = useState<ShowroomRow | null>(null);
    const [deleteProcessing, setDeleteProcessing] = useState(false);

    const openForm = (showroom: ShowroomRow | null) => {
        setEditing(showroom);
        setFormOpen(true);
    };

    const openDelete = (showroom: ShowroomRow) => {
        setDeleting(showroom);
        setDeleteOpen(true);
    };

    const search = filters.q ?? '';

    const handleSearch = (q: string) => {
        router.get(ShowroomController.index.url(), cleanQuery({ q }), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const handleDelete = () => {
        if (!deleting) {
            return;
        }

        router.delete(ShowroomController.destroy.url(deleting), {
            preserveScroll: true,
            onStart: () => setDeleteProcessing(true),
            onFinish: () => {
                setDeleteProcessing(false);
                setDeleteOpen(false);
            },
        });
    };

    const createButton = (
        <Button
            className="w-full rounded-xl sm:w-auto"
            onClick={() => openForm(null)}
        >
            <Plus />
            Tambah showroom
        </Button>
    );

    return (
        <>
            <Head title="Showroom" />
            <div className="flex min-w-0 flex-1 flex-col gap-6 p-4 md:p-6">
                <header className="space-y-2">
                    <p className="label-spec text-muted-foreground">
                        Master data
                    </p>
                    <h1 className="font-display text-3xl leading-none font-extrabold tracking-tight uppercase md:text-4xl">
                        Showroom
                    </h1>
                </header>

                <DataToolbar
                    search={search}
                    onSearch={handleSearch}
                    actions={createButton}
                />

                <DataTableShell>
                    {items.data.length === 0 ? (
                        <EmptyState
                            icon={Store}
                            title={
                                search
                                    ? 'Showroom tidak ditemukan'
                                    : 'Belum ada showroom'
                            }
                            description={
                                search
                                    ? `Tidak ada showroom yang cocok dengan "${search}".`
                                    : 'Tambahkan showroom pertama untuk menandai lokasi unit.'
                            }
                        />
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow className="hover:bg-transparent">
                                    <TableHead className="pl-4 label-spec text-muted-foreground">
                                        Nama
                                    </TableHead>
                                    <TableHead className="label-spec text-muted-foreground">
                                        Kota
                                    </TableHead>
                                    <TableHead className="label-spec text-muted-foreground">
                                        Telepon
                                    </TableHead>
                                    <TableHead className="label-spec text-muted-foreground">
                                        Koordinat
                                    </TableHead>
                                    <TableHead className="text-right label-spec text-muted-foreground">
                                        Mobil
                                    </TableHead>
                                    <TableHead className="w-12 pr-4">
                                        <span className="sr-only">Aksi</span>
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {items.data.map((showroom) => (
                                    <TableRow key={showroom.id}>
                                        <TableCell className="pl-4 font-medium">
                                            {showroom.name}
                                        </TableCell>
                                        <TableCell>{showroom.city}</TableCell>
                                        <TableCell className="font-mono text-sm whitespace-nowrap">
                                            {showroom.phone}
                                        </TableCell>
                                        <TableCell className="font-mono text-sm whitespace-nowrap text-muted-foreground">
                                            {Number(showroom.lat).toFixed(4)},{' '}
                                            {Number(showroom.lng).toFixed(4)}
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums">
                                            {showroom.cars_count}
                                        </TableCell>
                                        <TableCell className="pr-4 text-right">
                                            <RowActions
                                                items={[
                                                    {
                                                        label: 'Ubah',
                                                        icon: Pencil,
                                                        onSelect: () =>
                                                            openForm(showroom),
                                                    },
                                                    {
                                                        label: 'Hapus',
                                                        icon: Trash2,
                                                        destructive: true,
                                                        onSelect: () =>
                                                            openDelete(
                                                                showroom,
                                                            ),
                                                    },
                                                ]}
                                            />
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </DataTableShell>

                <PaginationBar meta={items} />
            </div>

            <ShowroomFormDialog
                open={formOpen}
                showroom={editing}
                onClose={() => setFormOpen(false)}
            />

            <ConfirmDeleteDialog
                open={deleteOpen}
                onOpenChange={setDeleteOpen}
                title={`Hapus showroom ${deleting?.name ?? ''}?`}
                description="Showroom akan dihapus permanen. Mobil yang terkait tetap ada, hanya lokasinya dikosongkan."
                onConfirm={handleDelete}
                processing={deleteProcessing}
            />
        </>
    );
}

function ShowroomFormDialog({
    open,
    showroom,
    onClose,
}: {
    open: boolean;
    showroom: ShowroomRow | null;
    onClose: () => void;
}) {
    return (
        <Dialog open={open} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-h-[90vh] overflow-y-auto rounded-2xl">
                <DialogHeader>
                    <DialogTitle>
                        {showroom ? 'Ubah showroom' : 'Tambah showroom'}
                    </DialogTitle>
                    <DialogDescription>
                        Latitude -90 sampai 90, longitude -180 sampai 180.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    key={showroom?.id ?? 'create'}
                    {...(showroom
                        ? ShowroomController.update.form(showroom)
                        : ShowroomController.store.form())}
                    options={{ preserveScroll: true }}
                    onSuccess={onClose}
                    className="grid gap-5"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="showroom-name">
                                    Nama showroom
                                </Label>
                                <Input
                                    id="showroom-name"
                                    name="name"
                                    defaultValue={showroom?.name ?? ''}
                                    maxLength={80}
                                    required
                                    autoFocus
                                    aria-invalid={
                                        errors.name ? true : undefined
                                    }
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-5 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="showroom-city">Kota</Label>
                                    <Input
                                        id="showroom-city"
                                        name="city"
                                        defaultValue={showroom?.city ?? ''}
                                        maxLength={60}
                                        required
                                        aria-invalid={
                                            errors.city ? true : undefined
                                        }
                                    />
                                    <InputError message={errors.city} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="showroom-phone">
                                        Telepon
                                    </Label>
                                    <Input
                                        id="showroom-phone"
                                        name="phone"
                                        type="tel"
                                        defaultValue={showroom?.phone ?? ''}
                                        maxLength={20}
                                        required
                                        className="font-mono"
                                        aria-invalid={
                                            errors.phone ? true : undefined
                                        }
                                    />
                                    <InputError message={errors.phone} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="showroom-address">Alamat</Label>
                                <Input
                                    id="showroom-address"
                                    name="address"
                                    defaultValue={showroom?.address ?? ''}
                                    maxLength={255}
                                    required
                                    aria-invalid={
                                        errors.address ? true : undefined
                                    }
                                />
                                <InputError message={errors.address} />
                            </div>

                            <div className="grid gap-5 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="showroom-lat">
                                        Latitude
                                    </Label>
                                    <Input
                                        id="showroom-lat"
                                        name="lat"
                                        type="number"
                                        step="any"
                                        min={-90}
                                        max={90}
                                        defaultValue={showroom?.lat ?? ''}
                                        required
                                        className="font-mono"
                                        aria-invalid={
                                            errors.lat ? true : undefined
                                        }
                                    />
                                    <InputError message={errors.lat} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="showroom-lng">
                                        Longitude
                                    </Label>
                                    <Input
                                        id="showroom-lng"
                                        name="lng"
                                        type="number"
                                        step="any"
                                        min={-180}
                                        max={180}
                                        defaultValue={showroom?.lng ?? ''}
                                        required
                                        className="font-mono"
                                        aria-invalid={
                                            errors.lng ? true : undefined
                                        }
                                    />
                                    <InputError message={errors.lng} />
                                </div>
                            </div>

                            <DialogFooter className="gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={onClose}
                                    disabled={processing}
                                >
                                    Batal
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    Simpan
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

ShowroomsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Showroom',
            href: ShowroomController.index(),
        },
    ],
};
