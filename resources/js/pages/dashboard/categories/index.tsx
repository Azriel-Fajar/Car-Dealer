import { Form, Head, router } from '@inertiajs/react';
import { Layers, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import CategoryController from '@/actions/App/Http/Controllers/Dashboard/CategoryController';
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

type CategoryRow = {
    id: number;
    name: string;
    slug: string;
    cars_count: number;
};

export default function CategoriesIndex({
    items,
    filters,
}: {
    items: Paginated<CategoryRow>;
    filters: IndexFilters;
}) {
    // Targets outlive the open flags so dialogs keep their content while closing.
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<CategoryRow | null>(null);
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [deleting, setDeleting] = useState<CategoryRow | null>(null);
    const [deleteProcessing, setDeleteProcessing] = useState(false);

    const openForm = (category: CategoryRow | null) => {
        setEditing(category);
        setFormOpen(true);
    };

    const openDelete = (category: CategoryRow) => {
        setDeleting(category);
        setDeleteOpen(true);
    };

    const search = filters.q ?? '';

    const handleSearch = (q: string) => {
        router.get(CategoryController.index.url(), cleanQuery({ q }), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const handleDelete = () => {
        if (!deleting) {
            return;
        }

        router.delete(CategoryController.destroy.url(deleting), {
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
            Tambah kategori
        </Button>
    );

    return (
        <>
            <Head title="Kategori" />
            <div className="flex min-w-0 flex-1 flex-col gap-6 p-4 md:p-6">
                <header className="space-y-2">
                    <p className="label-spec text-muted-foreground">
                        Master data
                    </p>
                    <h1 className="font-display text-3xl leading-none font-extrabold tracking-tight uppercase md:text-4xl">
                        Kategori
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
                            icon={Layers}
                            title={
                                search
                                    ? 'Kategori tidak ditemukan'
                                    : 'Belum ada kategori'
                            }
                            description={
                                search
                                    ? `Tidak ada kategori yang cocok dengan "${search}".`
                                    : 'Tambahkan kategori pertama untuk mengelompokkan mobil.'
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
                                {items.data.map((category) => (
                                    <TableRow key={category.id}>
                                        <TableCell className="pl-4 font-medium">
                                            {category.name}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {category.slug}
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums">
                                            {category.cars_count}
                                        </TableCell>
                                        <TableCell className="pr-4 text-right">
                                            <RowActions
                                                items={[
                                                    {
                                                        label: 'Ubah',
                                                        icon: Pencil,
                                                        onSelect: () =>
                                                            openForm(category),
                                                    },
                                                    {
                                                        label: 'Hapus',
                                                        icon: Trash2,
                                                        destructive: true,
                                                        onSelect: () =>
                                                            openDelete(
                                                                category,
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

            <CategoryFormDialog
                open={formOpen}
                category={editing}
                onClose={() => setFormOpen(false)}
            />

            <ConfirmDeleteDialog
                open={deleteOpen}
                onOpenChange={setDeleteOpen}
                title={`Hapus kategori ${deleting?.name ?? ''}?`}
                description="Kategori akan dihapus permanen. Kategori yang masih dipakai mobil tidak bisa dihapus."
                onConfirm={handleDelete}
                processing={deleteProcessing}
            />
        </>
    );
}

function CategoryFormDialog({
    open,
    category,
    onClose,
}: {
    open: boolean;
    category: CategoryRow | null;
    onClose: () => void;
}) {
    return (
        <Dialog open={open} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="rounded-2xl">
                <DialogHeader>
                    <DialogTitle>
                        {category ? 'Ubah kategori' : 'Tambah kategori'}
                    </DialogTitle>
                    <DialogDescription>
                        Nama kategori harus unik, maksimal 40 karakter.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    key={category?.id ?? 'create'}
                    {...(category
                        ? CategoryController.update.form(category)
                        : CategoryController.store.form())}
                    options={{ preserveScroll: true }}
                    onSuccess={onClose}
                    className="grid gap-5"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="category-name">
                                    Nama kategori
                                </Label>
                                <Input
                                    id="category-name"
                                    name="name"
                                    defaultValue={category?.name ?? ''}
                                    maxLength={40}
                                    required
                                    autoFocus
                                    aria-invalid={
                                        errors.name ? true : undefined
                                    }
                                />
                                <InputError message={errors.name} />
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

CategoriesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Kategori',
            href: CategoryController.index(),
        },
    ],
};
