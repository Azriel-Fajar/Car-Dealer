import { Form } from '@inertiajs/react';
import { Palette, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import CarColorController from '@/actions/App/Http/Controllers/Dashboard/CarColorController';
import { formatDelta, IMAGE_ACCEPT } from '@/components/dashboard/cars/media';
import type { CarColor, CarDetail } from '@/components/dashboard/cars/types';
import { useDeleteTarget } from '@/components/dashboard/cars/use-delete-target';
import { ConfirmDeleteDialog } from '@/components/dashboard/confirm-delete-dialog';
import { EmptyState } from '@/components/dashboard/empty-state';
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

export function ColorsTab({ car }: { car: CarDetail }) {
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<CarColor | null>(null);
    const deletion = useDeleteTarget<CarColor>();

    const openForm = (color: CarColor | null) => {
        setEditing(color);
        setFormOpen(true);
    };

    return (
        <section className="grid min-w-0 gap-4 rounded-2xl border border-border bg-card p-4 md:p-6">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div className="space-y-1">
                    <h2 className="font-semibold">Warna</h2>
                    <p className="text-sm text-muted-foreground">
                        Minimal 1 warna sebelum mobil bisa tayang.
                    </p>
                </div>
                <Button
                    className="w-full rounded-xl sm:w-auto"
                    onClick={() => openForm(null)}
                >
                    <Plus />
                    Tambah warna
                </Button>
            </div>

            {car.colors.length === 0 ? (
                <EmptyState
                    icon={Palette}
                    title="Belum ada warna"
                    description="Tambahkan pilihan warna beserta selisih harganya."
                />
            ) : (
                <ul className="divide-y divide-border rounded-xl border border-border">
                    {car.colors.map((color) => (
                        <li
                            key={color.id}
                            className="flex items-center gap-3 p-3"
                        >
                            <span
                                aria-hidden="true"
                                className="size-8 shrink-0 rounded-full ring-1 ring-border ring-offset-2 ring-offset-card"
                                style={{ backgroundColor: color.hex }}
                            />
                            <div className="min-w-0 flex-1">
                                <p className="truncate font-medium">
                                    {color.name}
                                </p>
                                <p className="font-mono text-xs text-muted-foreground uppercase">
                                    {color.hex}
                                </p>
                            </div>
                            <span className="shrink-0 text-sm tabular-nums">
                                {color.price_delta === 0
                                    ? 'Tanpa biaya'
                                    : formatDelta(color.price_delta)}
                            </span>
                            <span className="hidden size-12 shrink-0 overflow-hidden rounded-lg border border-border bg-muted sm:block">
                                {color.image_url && (
                                    <img
                                        src={color.image_url}
                                        alt={`Mobil warna ${color.name}`}
                                        className="size-full object-cover"
                                    />
                                )}
                            </span>
                            <RowActions
                                items={[
                                    {
                                        label: 'Ubah',
                                        icon: Pencil,
                                        onSelect: () => openForm(color),
                                    },
                                    {
                                        label: 'Hapus',
                                        icon: Trash2,
                                        destructive: true,
                                        onSelect: () => deletion.ask(color),
                                    },
                                ]}
                            />
                        </li>
                    ))}
                </ul>
            )}

            <ColorFormDialog
                car={car}
                open={formOpen}
                color={editing}
                onClose={() => setFormOpen(false)}
            />

            <ConfirmDeleteDialog
                open={deletion.open}
                onOpenChange={deletion.setOpen}
                title={`Hapus warna ${deletion.target?.name ?? ''}?`}
                description="Warna dan gambarnya akan dihapus permanen. Mobil yang tayang kembali ke draft bila ini warna terakhir."
                onConfirm={() =>
                    deletion.target &&
                    deletion.confirm(
                        CarColorController.destroy.url({
                            car: car.id,
                            color: deletion.target.id,
                        }),
                    )
                }
                processing={deletion.processing}
            />
        </section>
    );
}

function ColorFormDialog({
    car,
    open,
    color,
    onClose,
}: {
    car: CarDetail;
    open: boolean;
    color: CarColor | null;
    onClose: () => void;
}) {
    return (
        <Dialog open={open} onOpenChange={(next) => !next && onClose()}>
            <DialogContent className="rounded-2xl">
                <DialogHeader>
                    <DialogTitle>
                        {color ? 'Ubah warna' : 'Tambah warna'}
                    </DialogTitle>
                    <DialogDescription>
                        Gambar JPG, PNG, atau WebP, maksimal 4 MB.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    key={color?.id ?? 'create'}
                    {...(color
                        ? CarColorController.update.form({
                              car: car.id,
                              color: color.id,
                          })
                        : CarColorController.store.form(car.id))}
                    options={{ preserveScroll: true }}
                    errorBag="carColor"
                    onSuccess={onClose}
                    className="grid gap-5"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="color-name">Nama warna</Label>
                                <Input
                                    id="color-name"
                                    name="name"
                                    defaultValue={color?.name ?? ''}
                                    maxLength={40}
                                    required
                                    autoFocus
                                    aria-invalid={
                                        errors.name ? true : undefined
                                    }
                                />
                                <InputError message={errors.name} />
                            </div>

                            <HexField
                                defaultValue={color?.hex ?? '#000000'}
                                error={errors.hex}
                            />

                            <div className="grid gap-2">
                                <Label htmlFor="color-delta">
                                    Selisih harga
                                </Label>
                                <MoneyInput
                                    id="color-delta"
                                    name="price_delta"
                                    defaultValue={color?.price_delta ?? 0}
                                    allowNegative
                                    error={errors.price_delta}
                                />
                                <p className="text-xs text-muted-foreground">
                                    Ketik tanda minus (-) untuk warna yang lebih
                                    murah.
                                </p>
                            </div>

                            <div className="grid gap-2">
                                <Label>Gambar mobil (opsional)</Label>
                                <ImageInput
                                    name="image"
                                    currentUrl={color?.image_url}
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

/** Native picker + text field kept in sync; only the text field is submitted. */
function HexField({
    defaultValue,
    error,
}: {
    defaultValue: string;
    error?: string;
}) {
    const [hex, setHex] = useState(defaultValue);
    const validHex = /^#[0-9A-Fa-f]{6}$/.test(hex);

    return (
        <div className="grid gap-2">
            <Label htmlFor="color-hex">Kode warna</Label>
            <div className="flex items-center gap-2">
                <input
                    type="color"
                    aria-label="Pilih warna"
                    value={validHex ? hex : '#000000'}
                    onChange={(event) =>
                        setHex(event.target.value.toUpperCase())
                    }
                    className="h-9 w-12 shrink-0 cursor-pointer rounded-md border border-input bg-transparent p-1"
                />
                <Input
                    id="color-hex"
                    name="hex"
                    value={hex}
                    onChange={(event) => setHex(event.target.value)}
                    maxLength={7}
                    placeholder="#1A1A1A"
                    className="font-mono uppercase"
                    aria-invalid={error ? true : undefined}
                />
            </div>
            <InputError message={error} />
        </div>
    );
}
