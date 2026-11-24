import { Form } from '@inertiajs/react';
import { ListChecks, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import CarOptionController from '@/actions/App/Http/Controllers/Dashboard/CarOptionController';
import { SelectField } from '@/components/dashboard/cars/form-controls';
import { IMAGE_ACCEPT } from '@/components/dashboard/cars/media';
import type {
    CarDetail,
    CarOption,
    EnumOption,
} from '@/components/dashboard/cars/types';
import { useDeleteTarget } from '@/components/dashboard/cars/use-delete-target';
import { ConfirmDeleteDialog } from '@/components/dashboard/confirm-delete-dialog';
import { ImageInput } from '@/components/dashboard/image-input';
import { MoneyInput } from '@/components/dashboard/money-input';
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
import { formatRupiah } from '@/lib/format';

export function OptionsTab({
    car,
    groups,
}: {
    car: CarDetail;
    groups: EnumOption[];
}) {
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<CarOption | null>(null);
    const [defaultGroup, setDefaultGroup] = useState(groups[0]?.value ?? '');
    const deletion = useDeleteTarget<CarOption>();

    const openForm = (option: CarOption | null, group?: string) => {
        setEditing(option);
        setDefaultGroup(option?.group ?? group ?? groups[0]?.value ?? '');
        setFormOpen(true);
    };

    return (
        <section className="grid min-w-0 gap-6 rounded-2xl border border-border bg-card p-4 md:p-6">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div className="space-y-1">
                    <h2 className="font-semibold">Opsi</h2>
                    <p className="text-sm text-muted-foreground">
                        Pilihan tambahan yang bisa dipilih pembeli.
                    </p>
                </div>
                <Button
                    className="w-full rounded-xl sm:w-auto"
                    onClick={() => openForm(null)}
                >
                    <Plus />
                    Tambah opsi
                </Button>
            </div>

            {groups.map((group) => {
                const items = car.options.filter(
                    (option) => option.group === group.value,
                );

                return (
                    <div key={group.value} className="grid gap-3">
                        <div className="flex items-center justify-between gap-3">
                            <h3 className="label-spec text-muted-foreground">
                                {group.label}
                            </h3>
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => openForm(null, group.value)}
                            >
                                <Plus />
                                {group.label}
                            </Button>
                        </div>
                        {items.length === 0 ? (
                            <p className="rounded-xl border border-dashed border-border px-4 py-6 text-center text-sm text-muted-foreground">
                                Belum ada opsi {group.label.toLowerCase()}.
                            </p>
                        ) : (
                            <ul className="divide-y divide-border rounded-xl border border-border">
                                {items.map((option) => (
                                    <li
                                        key={option.id}
                                        className="flex items-center gap-3 p-3"
                                    >
                                        <span className="flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-border bg-muted text-muted-foreground">
                                            {option.image_url ? (
                                                <img
                                                    src={option.image_url}
                                                    alt={option.name}
                                                    className="size-full object-cover"
                                                />
                                            ) : (
                                                <ListChecks
                                                    aria-hidden="true"
                                                    className="size-4"
                                                />
                                            )}
                                        </span>
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate font-medium">
                                                {option.name}
                                            </p>
                                            {option.description && (
                                                <p className="line-clamp-2 text-xs text-muted-foreground">
                                                    {option.description}
                                                </p>
                                            )}
                                        </div>
                                        <span className="shrink-0 text-sm tabular-nums">
                                            {option.price === 0
                                                ? 'Gratis'
                                                : `+ ${formatRupiah(option.price)}`}
                                        </span>
                                        <RowActions
                                            items={[
                                                {
                                                    label: 'Ubah',
                                                    icon: Pencil,
                                                    onSelect: () =>
                                                        openForm(option),
                                                },
                                                {
                                                    label: 'Hapus',
                                                    icon: Trash2,
                                                    destructive: true,
                                                    onSelect: () =>
                                                        deletion.ask(option),
                                                },
                                            ]}
                                        />
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                );
            })}

            <OptionFormDialog
                car={car}
                groups={groups}
                open={formOpen}
                option={editing}
                defaultGroup={defaultGroup}
                onClose={() => setFormOpen(false)}
            />

            <ConfirmDeleteDialog
                open={deletion.open}
                onOpenChange={deletion.setOpen}
                title={`Hapus opsi ${deletion.target?.name ?? ''}?`}
                description="Opsi dan gambarnya akan dihapus permanen."
                onConfirm={() =>
                    deletion.target &&
                    deletion.confirm(
                        CarOptionController.destroy.url({
                            car: car.id,
                            option: deletion.target.id,
                        }),
                    )
                }
                processing={deletion.processing}
            />
        </section>
    );
}

function OptionFormDialog({
    car,
    groups,
    open,
    option,
    defaultGroup,
    onClose,
}: {
    car: CarDetail;
    groups: EnumOption[];
    open: boolean;
    option: CarOption | null;
    defaultGroup: string;
    onClose: () => void;
}) {
    return (
        <Dialog open={open} onOpenChange={(next) => !next && onClose()}>
            <DialogContent className="rounded-2xl">
                <DialogHeader>
                    <DialogTitle>
                        {option ? 'Ubah opsi' : 'Tambah opsi'}
                    </DialogTitle>
                    <DialogDescription>
                        Gambar JPG, PNG, atau WebP, maksimal 4 MB.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    key={option?.id ?? `create-${defaultGroup}`}
                    {...(option
                        ? CarOptionController.update.form({
                              car: car.id,
                              option: option.id,
                          })
                        : CarOptionController.store.form(car.id))}
                    options={{ preserveScroll: true }}
                    errorBag="carOption"
                    onSuccess={onClose}
                    className="grid gap-5"
                >
                    {({ processing, errors }) => (
                        <>
                            <SelectField
                                id="option-group"
                                name="group"
                                label="Grup"
                                options={groups}
                                defaultValue={defaultGroup}
                                error={errors.group}
                            />

                            <div className="grid gap-2">
                                <Label htmlFor="option-name">Nama opsi</Label>
                                <Input
                                    id="option-name"
                                    name="name"
                                    defaultValue={option?.name ?? ''}
                                    maxLength={60}
                                    required
                                    aria-invalid={
                                        errors.name ? true : undefined
                                    }
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="option-description">
                                    Deskripsi (opsional)
                                </Label>
                                <textarea
                                    id="option-description"
                                    name="description"
                                    defaultValue={option?.description ?? ''}
                                    maxLength={300}
                                    rows={3}
                                    className="min-h-20 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 md:text-sm"
                                    aria-invalid={
                                        errors.description ? true : undefined
                                    }
                                />
                                <InputError message={errors.description} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="option-price">Harga</Label>
                                <MoneyInput
                                    id="option-price"
                                    name="price"
                                    defaultValue={option?.price ?? 0}
                                    error={errors.price}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label>Gambar (opsional)</Label>
                                <ImageInput
                                    name="image"
                                    currentUrl={option?.image_url}
                                    accept={IMAGE_ACCEPT}
                                    error={errors.image}
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
