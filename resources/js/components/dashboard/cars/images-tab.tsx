import { Form, router } from '@inertiajs/react';
import {
    ChevronLeft,
    ChevronRight,
    ImageIcon,
    Star,
    Trash2,
    Upload,
} from 'lucide-react';
import { useState } from 'react';
import CarImageController from '@/actions/App/Http/Controllers/Dashboard/CarImageController';
import { IMAGE_ACCEPT } from '@/components/dashboard/cars/media';
import type { CarDetail, CarImage } from '@/components/dashboard/cars/types';
import { useDeleteTarget } from '@/components/dashboard/cars/use-delete-target';
import { ConfirmDeleteDialog } from '@/components/dashboard/confirm-delete-dialog';
import { EmptyState } from '@/components/dashboard/empty-state';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

const MAX_PER_CAR = 20;
const MAX_PER_UPLOAD = 10;

export function ImagesTab({ car }: { car: CarDetail }) {
    const [busy, setBusy] = useState(false);
    const [actionError, setActionError] = useState<string | undefined>();
    const deletion = useDeleteTarget<CarImage>();
    const images = car.images;
    const remaining = MAX_PER_CAR - images.length;

    // Own error bag so a stale-order error never marks the Info tab.
    const visitOptions = {
        preserveScroll: true,
        errorBag: 'carImageOrder',
        onStart: () => setBusy(true),
        onSuccess: () => setActionError(undefined),
        onError: (errors: Record<string, string>) =>
            setActionError(Object.values(errors)[0]),
        onFinish: () => setBusy(false),
    };

    // Buttons are not disabled while a request runs (that would drop keyboard
    // focus); repeated presses are ignored instead.
    const move = (index: number, offset: -1 | 1) => {
        if (busy) {
            return;
        }

        const ids = images.map((image) => image.id);
        const target = index + offset;
        [ids[index], ids[target]] = [ids[target], ids[index]];

        router.patch(
            CarImageController.order.url(car.id),
            { ids },
            visitOptions,
        );
    };

    const makeCover = (image: CarImage) => {
        if (busy) {
            return;
        }

        router.patch(
            CarImageController.cover.url({ car: car.id, image: image.id }),
            {},
            visitOptions,
        );
    };

    return (
        <section className="grid min-w-0 gap-5 rounded-2xl border border-border bg-card p-4 md:p-6">
            <div className="space-y-1">
                <h2 className="font-semibold">Foto</h2>
                <p className="text-sm text-muted-foreground">
                    {images.length} / {MAX_PER_CAR} foto. Foto cover tampil di
                    katalog; minimal 1 foto sebelum mobil bisa tayang.
                </p>
            </div>

            <UploadForm car={car} remaining={remaining} />

            <InputError message={actionError} />

            {images.length === 0 ? (
                <EmptyState
                    icon={ImageIcon}
                    title="Belum ada foto"
                    description="Unggah foto pertama; foto itu otomatis jadi cover."
                />
            ) : (
                <ol
                    aria-label="Urutan foto"
                    className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                >
                    {images.map((image, index) => (
                        <li
                            key={image.id}
                            className="overflow-hidden rounded-xl border border-border bg-background"
                        >
                            <div className="relative aspect-[4/3] bg-muted">
                                <img
                                    src={image.url}
                                    alt={`Foto ${index + 1} ${car.name}`}
                                    className="size-full object-cover"
                                />
                                <span className="absolute top-2 left-2 rounded-full bg-background/90 px-2 py-0.5 text-xs font-medium tabular-nums">
                                    {index + 1}
                                </span>
                                {image.is_cover && (
                                    <span className="absolute top-2 right-2 inline-flex items-center gap-1 rounded-full bg-primary px-2 py-0.5 text-xs font-medium text-primary-foreground">
                                        <Star
                                            aria-hidden="true"
                                            className="size-3 fill-current"
                                        />
                                        Cover
                                    </span>
                                )}
                            </div>
                            <div className="flex flex-wrap items-center gap-1 p-2">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="size-8"
                                    aria-label={`Geser foto ${index + 1} ke kiri`}
                                    disabled={index === 0}
                                    onClick={() => move(index, -1)}
                                >
                                    <ChevronLeft />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="size-8"
                                    aria-label={`Geser foto ${index + 1} ke kanan`}
                                    disabled={index === images.length - 1}
                                    onClick={() => move(index, 1)}
                                >
                                    <ChevronRight />
                                </Button>
                                {!image.is_cover && (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="ml-auto"
                                        onClick={() => makeCover(image)}
                                    >
                                        Jadikan cover
                                    </Button>
                                )}
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className={
                                        image.is_cover
                                            ? 'ml-auto size-8 text-destructive'
                                            : 'size-8 text-destructive'
                                    }
                                    aria-label={`Hapus foto ${index + 1}`}
                                    onClick={() => deletion.ask(image)}
                                >
                                    <Trash2 />
                                </Button>
                            </div>
                        </li>
                    ))}
                </ol>
            )}

            <ConfirmDeleteDialog
                open={deletion.open}
                onOpenChange={deletion.setOpen}
                title="Hapus foto ini?"
                description="Foto akan dihapus permanen. Bila ini cover, foto berikutnya jadi cover; mobil yang tayang kembali ke draft bila ini foto terakhir."
                onConfirm={() =>
                    deletion.target &&
                    deletion.confirm(
                        CarImageController.destroy.url({
                            car: car.id,
                            image: deletion.target.id,
                        }),
                    )
                }
                processing={deletion.processing}
            />
        </section>
    );
}

function UploadForm({ car, remaining }: { car: CarDetail; remaining: number }) {
    const [count, setCount] = useState(0);

    return (
        <Form
            {...CarImageController.store.form(car.id)}
            options={{ preserveScroll: true }}
            errorBag="carImages"
            resetOnSuccess
            onSuccess={() => setCount(0)}
            className="grid gap-2 rounded-xl border border-dashed border-border p-4"
        >
            {({ processing, errors }) => {
                const fileErrors = Object.entries(errors)
                    .filter(([key]) => key.startsWith('images.'))
                    .map(([key, message]) => ({
                        key,
                        message: `Foto ${Number(key.split('.')[1]) + 1}: ${message}`,
                    }));

                return (
                    <>
                        <label
                            htmlFor="car-images"
                            className="text-sm font-medium"
                        >
                            Unggah foto
                        </label>
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <input
                                id="car-images"
                                type="file"
                                name="images[]"
                                accept={IMAGE_ACCEPT}
                                multiple
                                disabled={remaining <= 0}
                                onChange={(event) =>
                                    setCount(event.target.files?.length ?? 0)
                                }
                                className="min-w-0 flex-1 text-sm file:mr-3 file:rounded-md file:border file:border-border file:bg-muted file:px-3 file:py-1.5 file:text-sm file:font-medium"
                            />
                            <Button
                                type="submit"
                                className="rounded-xl"
                                disabled={processing || count === 0}
                            >
                                {processing ? <Spinner /> : <Upload />}
                                Unggah{count > 0 ? ` ${count} foto` : ''}
                            </Button>
                        </div>
                        <p className="text-xs text-muted-foreground">
                            JPG, PNG, atau WebP, maksimal 4 MB per foto,{' '}
                            {MAX_PER_UPLOAD} foto per unggahan. Sisa slot:{' '}
                            {Math.max(remaining, 0)}.
                        </p>
                        <InputError message={errors.images} />
                        {fileErrors.map((error) => (
                            <InputError
                                key={error.key}
                                message={error.message}
                            />
                        ))}
                    </>
                );
            }}
        </Form>
    );
}
