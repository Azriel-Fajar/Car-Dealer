import { Form, Head, router } from '@inertiajs/react';
import { Pencil, Plus, Tags, Trash2 } from 'lucide-react';
import { useState } from 'react';
import BrandController from '@/actions/App/Http/Controllers/Dashboard/BrandController';
import { ConfirmDeleteDialog } from '@/components/dashboard/confirm-delete-dialog';
import { DataTableShell } from '@/components/dashboard/data-table-shell';
import { DataToolbar } from '@/components/dashboard/data-toolbar';
import { EmptyState } from '@/components/dashboard/empty-state';
import { ImageInput } from '@/components/dashboard/image-input';
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

type BrandRow = {
    id: number;
    name: string;
    slug: string;
    logo_url: string | null;
    cars_count: number;
};

export default function BrandsIndex({
    items,
    filters,
}: {
    items: Paginated<BrandRow>;
    filters: IndexFilters;
}) {
    // Targets outlive the open flags so dialogs keep their content while closing.
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<BrandRow | null>(null);
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [deleting, setDeleting] = useState<BrandRow | null>(null);
    const [deleteProcessing, setDeleteProcessing] = useState(false);

    const openForm = (brand: BrandRow | null) => {
        setEditing(brand);
        setFormOpen(true);
    };

    const openDelete = (brand: BrandRow) => {
        setDeleting(brand);
        setDeleteOpen(true);
    };

    const search = filters.q ?? '';

    const handleSearch = (q: string) => {
        router.get(BrandController.index.url(), cleanQuery({ q }), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const handleDelete = () => {
        if (!deleting) {
            return;
        }

        router.delete(BrandController.destroy.url(deleting), {
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
            Tambah brand
        </Button>
    );

    return (
        <>
            <Head title="Brand" />
            <div className="flex min-w-0 flex-1 flex-col gap-6 p-4 md:p-6">
                <header className="space-y-2">
                    <p className="label-spec text-muted-foreground">
                        Master data
                    </p>
                    <h1 className="font-display text-3xl leading-none font-extrabold tracking-tight uppercase md:text-4xl">
                        Brand
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
                            icon={Tags}
                            title={
                                search
                                    ? 'Brand tidak ditemukan'
                                    : 'Belum ada brand'
                            }
                            description={
                                search
                                    ? `Tidak ada brand yang cocok dengan "${search}".`
                                    : 'Tambahkan brand pertama untuk mulai mengisi katalog.'
                            }
                        />
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow className="hover:bg-transparent">
                                    <TableHead className="w-16 pl-4 label-spec text-muted-foreground">
                                        Logo
                                    </TableHead>
                                    <TableHead className="label-spec text-muted-foreground">
                                        Nama
                                    </TableHead>
                                    <TableHead className="label-spec text-muted-foreground">
                                        Slug
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
                                {items.data.map((brand) => (
                                    <TableRow key={brand.id}>
                                        <TableCell className="pl-4">
                                            <BrandLogo brand={brand} />
                                        </TableCell>
                                        <TableCell className="font-medium">
                                            {brand.name}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {brand.slug}
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums">
                                            {brand.cars_count}
                                        </TableCell>
                                        <TableCell className="pr-4 text-right">
                                            <RowActions
                                                items={[
                                                    {
                                                        label: 'Ubah',
                                                        icon: Pencil,
                                                        onSelect: () =>
                                                            openForm(brand),
                                                    },
                                                    {
                                                        label: 'Hapus',
                                                        icon: Trash2,
                                                        destructive: true,
                                                        onSelect: () =>
                                                            openDelete(brand),
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

            <BrandFormDialog
                open={formOpen}
                brand={editing}
                onClose={() => setFormOpen(false)}
            />

            <ConfirmDeleteDialog
                open={deleteOpen}
                onOpenChange={setDeleteOpen}
                title={`Hapus brand ${deleting?.name ?? ''}?`}
                description="Brand dan logonya akan dihapus permanen. Brand yang masih dipakai mobil tidak bisa dihapus."
                onConfirm={handleDelete}
                processing={deleteProcessing}
            />
        </>
    );
}

function BrandLogo({ brand }: { brand: BrandRow }) {
    return (
        <span className="flex size-10 items-center justify-center overflow-hidden rounded-lg border border-border bg-muted">
            {brand.logo_url ? (
                <img
                    src={brand.logo_url}
                    alt={`Logo ${brand.name}`}
                    className="size-full object-contain p-1"
                />
            ) : (
                <span className="font-display text-sm font-extrabold text-muted-foreground uppercase">
                    {brand.name.charAt(0)}
                </span>
            )}
        </span>
    );
}

function BrandFormDialog({
    open,
    brand,
    onClose,
}: {
    open: boolean;
    brand: BrandRow | null;
    onClose: () => void;
}) {
    return (
        <Dialog open={open} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="rounded-2xl">
                <DialogHeader>
                    <DialogTitle>
                        {brand ? 'Ubah brand' : 'Tambah brand'}
                    </DialogTitle>
                    <DialogDescription>
                        Logo PNG atau WebP, maksimal 1 MB.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    key={brand?.id ?? 'create'}
                    {...(brand
                        ? BrandController.update.form(brand)
                        : BrandController.store.form())}
                    options={{ preserveScroll: true }}
                    onSuccess={onClose}
                    className="grid gap-5"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="brand-name">Nama brand</Label>
                                <Input
                                    id="brand-name"
                                    name="name"
                                    defaultValue={brand?.name ?? ''}
                                    maxLength={60}
                                    required
                                    autoFocus
                                    aria-invalid={
                                        errors.name ? true : undefined
                                    }
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label>Logo</Label>
                                <ImageInput
                                    name="logo"
                                    currentUrl={brand?.logo_url}
                                    accept="image/png,image/webp"
                                    error={errors.logo}
                                />
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

BrandsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Brand',
            href: BrandController.index(),
        },
    ],
};
