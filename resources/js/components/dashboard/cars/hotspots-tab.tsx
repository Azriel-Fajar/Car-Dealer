import { router } from '@inertiajs/react';
import { ImageIcon, MapPin, Trash2 } from 'lucide-react';
import type { MouseEvent } from 'react';
import { useState } from 'react';
import CarHotspotController from '@/actions/App/Http/Controllers/Dashboard/CarHotspotController';
import type { CarDetail, CarHotspot } from '@/components/dashboard/cars/types';
import { EmptyState } from '@/components/dashboard/empty-state';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { MAX_HOTSPOTS, pointFromClick, toPercent } from '@/lib/hotspots';
import { cn } from '@/lib/utils';

const textareaClass =
    'min-h-24 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 md:text-sm';

export function HotspotsTab({
    car,
    onOpenImages,
}: {
    car: CarDetail;
    onOpenImages: () => void;
}) {
    const cover = car.images.find((image) => image.is_cover);
    const [hotspots, setHotspots] = useState<CarHotspot[]>(car.hotspots ?? []);
    const [selected, setSelected] = useState<number | null>(null);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);
    const full = hotspots.length >= MAX_HOTSPOTS;

    if (!cover) {
        return (
            <section className="rounded-2xl border border-border bg-card">
                <EmptyState
                    icon={ImageIcon}
                    title="Unggah foto & pilih cover dulu"
                    description="Hotspot ditempatkan di atas foto cover mobil."
                    action={
                        <Button
                            variant="outline"
                            className="rounded-xl"
                            onClick={onOpenImages}
                        >
                            Buka tab Foto
                        </Button>
                    }
                />
            </section>
        );
    }

    const addAt = (event: MouseEvent<HTMLButtonElement>) => {
        if (full) {
            return;
        }

        // A keyboard press has no pointer position: place it in the middle.
        const point =
            event.detail === 0
                ? { x: 50, y: 50 }
                : pointFromClick(
                      event.currentTarget.getBoundingClientRect(),
                      event.clientX,
                      event.clientY,
                  );

        setHotspots([...hotspots, { ...point, title: '', description: '' }]);
        setSelected(hotspots.length);
    };

    const change = (index: number, patch: Partial<CarHotspot>) =>
        setHotspots(
            hotspots.map((hotspot, i) =>
                i === index ? { ...hotspot, ...patch } : hotspot,
            ),
        );

    const remove = (index: number) => {
        setHotspots(hotspots.filter((_, i) => i !== index));
        setSelected(null);
        setErrors({});
    };

    const save = () => {
        if (processing) {
            return;
        }

        router.put(
            CarHotspotController.update.url(car.id),
            { hotspots },
            {
                preserveScroll: true,
                // Own error bag so these errors never mark the Info tab.
                errorBag: 'carHotspots',
                onStart: () => setProcessing(true),
                onSuccess: () => setErrors({}),
                onError: (next) => setErrors(next),
                onFinish: () => setProcessing(false),
            },
        );
    };

    const hasError = (index: number) =>
        Object.keys(errors).some((key) => key.startsWith(`hotspots.${index}.`));
    const current = selected === null ? undefined : hotspots[selected];

    return (
        <section className="grid min-w-0 gap-5 rounded-2xl border border-border bg-card p-4 md:p-6">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div className="space-y-1">
                    <h2 className="font-semibold">Hotspot</h2>
                    <p className="text-sm text-muted-foreground">
                        {hotspots.length} / {MAX_HOTSPOTS} hotspot. Klik foto
                        cover untuk menambah titik, lalu isi judul dan
                        deskripsinya.
                    </p>
                </div>
                <Button className="w-full rounded-xl sm:w-auto" onClick={save}>
                    {processing && <Spinner />}
                    Simpan hotspot
                </Button>
            </div>

            <InputError message={errors.hotspots} />

            <div className="grid min-w-0 gap-5 lg:grid-cols-[minmax(0,1fr)_20rem]">
                <div className="relative min-w-0 self-start overflow-hidden rounded-xl border border-border bg-muted">
                    <img
                        src={cover.url}
                        alt={`Foto cover ${car.name}`}
                        draggable={false}
                        className="block h-auto w-full select-none"
                    />
                    <button
                        type="button"
                        aria-label="Tambah hotspot di titik yang diklik"
                        disabled={full}
                        onClick={addAt}
                        className={cn(
                            'absolute inset-0 focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none focus-visible:ring-inset',
                            full ? 'cursor-not-allowed' : 'cursor-crosshair',
                        )}
                    />
                    {hotspots.map((hotspot, index) => (
                        <button
                            key={index}
                            type="button"
                            aria-label={`Hotspot ${index + 1}${hotspot.title ? `: ${hotspot.title}` : ''}`}
                            aria-pressed={selected === index}
                            onClick={() => setSelected(index)}
                            style={{
                                left: `${hotspot.x}%`,
                                top: `${hotspot.y}%`,
                            }}
                            className={cn(
                                'absolute z-10 flex size-7 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full border-2 text-xs font-semibold tabular-nums shadow-md transition-transform',
                                selected === index
                                    ? 'scale-110 border-background bg-primary text-primary-foreground'
                                    : 'border-primary bg-background text-foreground',
                                hasError(index) &&
                                    'border-destructive ring-2 ring-destructive/40',
                            )}
                        >
                            {index + 1}
                        </button>
                    ))}
                </div>

                <div className="grid min-w-0 content-start gap-4">
                    {current && selected !== null ? (
                        <HotspotFields
                            key={selected}
                            index={selected}
                            hotspot={current}
                            errors={errors}
                            onChange={(patch) => change(selected, patch)}
                            onRemove={() => remove(selected)}
                        />
                    ) : (
                        <p className="rounded-xl border border-dashed border-border p-4 text-sm text-muted-foreground">
                            {hotspots.length === 0
                                ? 'Belum ada hotspot. Klik foto untuk menambah titik pertama.'
                                : 'Pilih nomor hotspot untuk mengubahnya.'}
                        </p>
                    )}

                    {full && (
                        <p className="text-sm text-muted-foreground">
                            Batas {MAX_HOTSPOTS} hotspot tercapai. Hapus satu
                            untuk menambah yang baru.
                        </p>
                    )}

                    {hotspots.length > 0 && (
                        <ol
                            aria-label="Daftar hotspot"
                            className="divide-y divide-border rounded-xl border border-border"
                        >
                            {hotspots.map((hotspot, index) => (
                                <li key={index}>
                                    <button
                                        type="button"
                                        onClick={() => setSelected(index)}
                                        className={cn(
                                            'flex w-full items-center gap-3 p-3 text-left text-sm hover:bg-muted/60',
                                            selected === index && 'bg-muted',
                                        )}
                                    >
                                        <span className="flex size-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-semibold text-primary-foreground tabular-nums">
                                            {index + 1}
                                        </span>
                                        <span
                                            className={cn(
                                                'min-w-0 flex-1 truncate',
                                                !hotspot.title &&
                                                    'text-muted-foreground italic',
                                            )}
                                        >
                                            {hotspot.title || 'Tanpa judul'}
                                        </span>
                                        <span className="shrink-0 font-mono text-xs text-muted-foreground">
                                            {hotspot.x}, {hotspot.y}
                                        </span>
                                        {hasError(index) && (
                                            <span
                                                aria-label="Ada kesalahan"
                                                className="size-1.5 shrink-0 rounded-full bg-destructive"
                                            />
                                        )}
                                    </button>
                                </li>
                            ))}
                        </ol>
                    )}
                </div>
            </div>
        </section>
    );
}

function HotspotFields({
    index,
    hotspot,
    errors,
    onChange,
    onRemove,
}: {
    index: number;
    hotspot: CarHotspot;
    errors: Record<string, string>;
    onChange: (patch: Partial<CarHotspot>) => void;
    onRemove: () => void;
}) {
    const error = (field: keyof CarHotspot) =>
        errors[`hotspots.${index}.${field}`];
    const id = (field: string) => `hotspot-${field}`;

    return (
        <div className="grid gap-4 rounded-xl border border-border p-4">
            <div className="flex items-center justify-between gap-2">
                <h3 className="flex items-center gap-2 text-sm font-semibold">
                    <MapPin aria-hidden="true" className="size-4" />
                    Hotspot {index + 1}
                </h3>
                <Button
                    variant="ghost"
                    size="sm"
                    className="text-destructive"
                    onClick={onRemove}
                >
                    <Trash2 />
                    Hapus
                </Button>
            </div>

            <div className="grid gap-2">
                <Label htmlFor={id('title')}>Judul</Label>
                <Input
                    id={id('title')}
                    value={hotspot.title}
                    onChange={(event) =>
                        onChange({ title: event.target.value })
                    }
                    maxLength={40}
                    autoFocus
                    aria-invalid={error('title') ? true : undefined}
                />
                <InputError message={error('title')} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor={id('description')}>Deskripsi</Label>
                <textarea
                    id={id('description')}
                    value={hotspot.description}
                    onChange={(event) =>
                        onChange({ description: event.target.value })
                    }
                    maxLength={200}
                    className={textareaClass}
                    aria-invalid={error('description') ? true : undefined}
                />
                <InputError message={error('description')} />
            </div>

            <div className="grid grid-cols-2 gap-3">
                {(['x', 'y'] as const).map((axis) => (
                    <div key={axis} className="grid gap-2">
                        <Label htmlFor={id(axis)}>
                            Posisi {axis.toUpperCase()}
                            <span className="ml-1 font-normal text-muted-foreground">
                                (%)
                            </span>
                        </Label>
                        <Input
                            id={id(axis)}
                            type="number"
                            inputMode="decimal"
                            min={0}
                            max={100}
                            step={0.1}
                            value={hotspot[axis]}
                            onChange={(event) => {
                                const value = event.target.valueAsNumber;

                                onChange({
                                    [axis]: Number.isNaN(value)
                                        ? 0
                                        : toPercent(value),
                                });
                            }}
                            className="tabular-nums"
                            aria-invalid={error(axis) ? true : undefined}
                        />
                        <InputError message={error(axis)} />
                    </div>
                ))}
            </div>
        </div>
    );
}
