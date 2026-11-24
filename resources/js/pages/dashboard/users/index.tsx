import { Form, Head, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2, Users } from 'lucide-react';
import { useState } from 'react';
import UserController from '@/actions/App/Http/Controllers/Dashboard/UserController';
import { ConfirmDeleteDialog } from '@/components/dashboard/confirm-delete-dialog';
import { DataTableShell } from '@/components/dashboard/data-table-shell';
import { DataToolbar } from '@/components/dashboard/data-toolbar';
import { EmptyState } from '@/components/dashboard/empty-state';
import { PaginationBar } from '@/components/dashboard/pagination-bar';
import { RowActions } from '@/components/dashboard/row-actions';
import { StatusPill } from '@/components/dashboard/status-pill';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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

type RoleOption = { value: string; label: string };

type UserRow = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    role: string;
    role_label: string;
    is_active: boolean;
    created_at: string | null;
    is_self: boolean;
};

const ALL = 'all';

const roleTone = (role: string) =>
    role === 'admin' ? 'info' : role === 'sales' ? 'warning' : 'neutral';

export default function UsersIndex({
    items,
    filters,
    roles,
}: {
    items: Paginated<UserRow>;
    filters: IndexFilters;
    roles: RoleOption[];
}) {
    // Targets outlive the open flags so dialogs keep their content while closing.
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<UserRow | null>(null);
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [deleting, setDeleting] = useState<UserRow | null>(null);
    const [deleteProcessing, setDeleteProcessing] = useState(false);

    const openForm = (user: UserRow | null) => {
        setEditing(user);
        setFormOpen(true);
    };

    const openDelete = (user: UserRow) => {
        setDeleting(user);
        setDeleteOpen(true);
    };

    const search = filters.q ?? '';
    const role = filters.role ?? '';
    const active = filters.active ?? '';

    const visit = (next: { q?: string; role?: string; active?: string }) => {
        router.get(
            UserController.index.url(),
            cleanQuery({ q: search, role, active, ...next }),
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const handleDelete = () => {
        if (!deleting) {
            return;
        }

        router.delete(UserController.destroy.url(deleting), {
            preserveScroll: true,
            onStart: () => setDeleteProcessing(true),
            onFinish: () => {
                setDeleteProcessing(false);
                setDeleteOpen(false);
            },
        });
    };

    const filterControls = (
        <>
            <Select
                value={role || ALL}
                onValueChange={(value) =>
                    visit({ role: value === ALL ? '' : value })
                }
            >
                <SelectTrigger
                    aria-label="Filter peran"
                    className="h-10 w-40 rounded-xl bg-card"
                >
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value={ALL}>Semua peran</SelectItem>
                    {roles.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            <Select
                value={active || ALL}
                onValueChange={(value) =>
                    visit({ active: value === ALL ? '' : value })
                }
            >
                <SelectTrigger
                    aria-label="Filter status"
                    className="h-10 w-40 rounded-xl bg-card"
                >
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value={ALL}>Semua status</SelectItem>
                    <SelectItem value="1">Aktif</SelectItem>
                    <SelectItem value="0">Nonaktif</SelectItem>
                </SelectContent>
            </Select>
        </>
    );

    const createButton = (
        <Button
            className="w-full rounded-xl sm:w-auto"
            onClick={() => openForm(null)}
        >
            <Plus />
            Tambah pengguna
        </Button>
    );

    const filtered = search !== '' || role !== '' || active !== '';

    return (
        <>
            <Head title="Pengguna" />
            <div className="flex min-w-0 flex-1 flex-col gap-6 p-4 md:p-6">
                <header className="space-y-2">
                    <p className="label-spec text-muted-foreground">
                        Master data
                    </p>
                    <h1 className="font-display text-3xl leading-none font-extrabold tracking-tight uppercase md:text-4xl">
                        Pengguna
                    </h1>
                </header>

                <DataToolbar
                    search={search}
                    onSearch={(q) => visit({ q })}
                    filters={filterControls}
                    actions={createButton}
                />

                <DataTableShell>
                    {items.data.length === 0 ? (
                        <EmptyState
                            icon={Users}
                            title={
                                filtered
                                    ? 'Pengguna tidak ditemukan'
                                    : 'Belum ada pengguna'
                            }
                            description={
                                filtered
                                    ? 'Tidak ada pengguna yang cocok dengan pencarian atau filter ini.'
                                    : 'Tambahkan akun pertama untuk tim dealer.'
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
                                        Email
                                    </TableHead>
                                    <TableHead className="label-spec text-muted-foreground">
                                        Telepon
                                    </TableHead>
                                    <TableHead className="label-spec text-muted-foreground">
                                        Peran
                                    </TableHead>
                                    <TableHead className="label-spec text-muted-foreground">
                                        Status
                                    </TableHead>
                                    <TableHead className="label-spec text-muted-foreground">
                                        Dibuat
                                    </TableHead>
                                    <TableHead className="w-12 pr-4">
                                        <span className="sr-only">Aksi</span>
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {items.data.map((user) => (
                                    <TableRow key={user.id}>
                                        <TableCell className="pl-4 font-medium">
                                            {user.name}
                                            {user.is_self && (
                                                <span className="ml-2 text-xs font-normal text-muted-foreground">
                                                    (Anda)
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell>{user.email}</TableCell>
                                        <TableCell className="font-mono text-sm whitespace-nowrap">
                                            {user.phone ?? '-'}
                                        </TableCell>
                                        <TableCell>
                                            <StatusPill
                                                tone={roleTone(user.role)}
                                            >
                                                {user.role_label}
                                            </StatusPill>
                                        </TableCell>
                                        <TableCell>
                                            <StatusPill
                                                tone={
                                                    user.is_active
                                                        ? 'success'
                                                        : 'neutral'
                                                }
                                            >
                                                {user.is_active
                                                    ? 'Aktif'
                                                    : 'Nonaktif'}
                                            </StatusPill>
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap text-muted-foreground">
                                            {user.created_at}
                                        </TableCell>
                                        <TableCell className="pr-4 text-right">
                                            <RowActions
                                                items={[
                                                    {
                                                        label: 'Ubah',
                                                        icon: Pencil,
                                                        onSelect: () =>
                                                            openForm(user),
                                                    },
                                                    ...(user.is_self
                                                        ? []
                                                        : [
                                                              {
                                                                  label: 'Hapus',
                                                                  icon: Trash2,
                                                                  destructive: true,
                                                                  onSelect:
                                                                      () =>
                                                                          openDelete(
                                                                              user,
                                                                          ),
                                                              },
                                                          ]),
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

            <UserFormDialog
                open={formOpen}
                user={editing}
                roles={roles}
                onClose={() => setFormOpen(false)}
            />

            <ConfirmDeleteDialog
                open={deleteOpen}
                onOpenChange={setDeleteOpen}
                title={`Hapus pengguna ${deleting?.name ?? ''}?`}
                description="Akun akan dihapus permanen dan tidak bisa dikembalikan."
                onConfirm={handleDelete}
                processing={deleteProcessing}
            />
        </>
    );
}

function UserFormDialog({
    open,
    user,
    roles,
    onClose,
}: {
    open: boolean;
    user: UserRow | null;
    roles: RoleOption[];
    onClose: () => void;
}) {
    const isSelf = user?.is_self ?? false;

    return (
        <Dialog open={open} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-h-[90vh] overflow-y-auto rounded-2xl">
                <DialogHeader>
                    <DialogTitle>
                        {user ? 'Ubah pengguna' : 'Tambah pengguna'}
                    </DialogTitle>
                    <DialogDescription>
                        {user
                            ? 'Kosongkan kata sandi jika tidak ingin mengubahnya.'
                            : 'Akun yang dibuat admin langsung terverifikasi.'}
                    </DialogDescription>
                </DialogHeader>

                <Form
                    key={user?.id ?? 'create'}
                    {...(user
                        ? UserController.update.form(user)
                        : UserController.store.form())}
                    options={{ preserveScroll: true }}
                    resetOnSuccess={['password', 'password_confirmation']}
                    onSuccess={onClose}
                    className="grid gap-5"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="user-name">Nama</Label>
                                <Input
                                    id="user-name"
                                    name="name"
                                    defaultValue={user?.name ?? ''}
                                    maxLength={100}
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
                                    <Label htmlFor="user-email">Email</Label>
                                    <Input
                                        id="user-email"
                                        name="email"
                                        type="email"
                                        defaultValue={user?.email ?? ''}
                                        maxLength={255}
                                        required
                                        aria-invalid={
                                            errors.email ? true : undefined
                                        }
                                    />
                                    <InputError message={errors.email} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="user-phone">
                                        Telepon (opsional)
                                    </Label>
                                    <Input
                                        id="user-phone"
                                        name="phone"
                                        type="tel"
                                        defaultValue={user?.phone ?? ''}
                                        maxLength={20}
                                        className="font-mono"
                                        aria-invalid={
                                            errors.phone ? true : undefined
                                        }
                                    />
                                    <InputError message={errors.phone} />
                                </div>
                            </div>

                            <div className="grid gap-5 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="user-role">Peran</Label>
                                    <Select
                                        name="role"
                                        defaultValue={user?.role ?? 'sales'}
                                        disabled={isSelf}
                                    >
                                        <SelectTrigger
                                            id="user-role"
                                            className="w-full"
                                            aria-invalid={
                                                errors.role ? true : undefined
                                            }
                                        >
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {roles.map((option) => (
                                                <SelectItem
                                                    key={option.value}
                                                    value={option.value}
                                                >
                                                    {option.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {isSelf && (
                                        <input
                                            type="hidden"
                                            name="role"
                                            value={user?.role}
                                        />
                                    )}
                                    <InputError message={errors.role} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="user-active">Status</Label>
                                    <Select
                                        name="is_active"
                                        defaultValue={
                                            user && !user.is_active ? '0' : '1'
                                        }
                                        disabled={isSelf}
                                    >
                                        <SelectTrigger
                                            id="user-active"
                                            className="w-full"
                                            aria-invalid={
                                                errors.is_active
                                                    ? true
                                                    : undefined
                                            }
                                        >
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="1">
                                                Aktif
                                            </SelectItem>
                                            <SelectItem value="0">
                                                Nonaktif
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {isSelf && (
                                        <input
                                            type="hidden"
                                            name="is_active"
                                            value="1"
                                        />
                                    )}
                                    <InputError message={errors.is_active} />
                                </div>
                            </div>

                            <div className="grid gap-5 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="user-password">
                                        Kata sandi
                                    </Label>
                                    <Input
                                        id="user-password"
                                        name="password"
                                        type="password"
                                        autoComplete="new-password"
                                        required={!user}
                                        aria-invalid={
                                            errors.password ? true : undefined
                                        }
                                    />
                                    <InputError message={errors.password} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="user-password-confirmation">
                                        Ulangi kata sandi
                                    </Label>
                                    <Input
                                        id="user-password-confirmation"
                                        name="password_confirmation"
                                        type="password"
                                        autoComplete="new-password"
                                        required={!user}
                                    />
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

UsersIndex.layout = {
    breadcrumbs: [
        {
            title: 'Pengguna',
            href: UserController.index(),
        },
    ],
};
