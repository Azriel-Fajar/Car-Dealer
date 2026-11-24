import { useState } from 'react';
import {
    NumberField,
    SelectField,
} from '@/components/dashboard/cars/form-controls';
import type {
    CarDetail,
    CarFormOptions,
} from '@/components/dashboard/cars/types';
import { MoneyInput } from '@/components/dashboard/money-input';
import InputError from '@/components/input-error';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export const INFO_FIELDS = [
    'brand_id',
    'category_id',
    'showroom_id',
    'name',
    'year',
    'base_price',
    'condition',
    'mileage_km',
    'stock',
    'status',
    'badge',
    'description',
    'is_featured',
];

const textareaClass =
    'min-h-32 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 md:text-sm';

export function InfoFields({
    car,
    options,
    errors,
}: {
    car?: CarDetail;
    options: CarFormOptions;
    errors: Record<string, string>;
}) {
    const [featured, setFeatured] = useState(car?.is_featured ?? false);
    const maxYear = new Date().getFullYear() + 1;

    const toOptions = (items: { id: number; name: string }[]) =>
        items.map((item) => ({ value: String(item.id), label: item.name }));

    return (
        <div className="grid gap-5">
            <div className="grid gap-5 sm:grid-cols-2">
                <SelectField
                    id="car-brand"
                    name="brand_id"
                    label="Brand"
                    options={toOptions(options.brands)}
                    defaultValue={car ? String(car.brand_id) : null}
                    placeholder="Pilih brand"
                    error={errors.brand_id}
                />
                <SelectField
                    id="car-category"
                    name="category_id"
                    label="Kategori"
                    options={toOptions(options.categories)}
                    defaultValue={car ? String(car.category_id) : null}
                    placeholder="Pilih kategori"
                    error={errors.category_id}
                />
            </div>

            <div className="grid gap-5 sm:grid-cols-[1fr_8rem]">
                <div className="grid gap-2">
                    <Label htmlFor="car-name">Nama mobil</Label>
                    <Input
                        id="car-name"
                        name="name"
                        defaultValue={car?.name ?? ''}
                        maxLength={80}
                        required
                        placeholder="Fortuner GR Sport"
                        aria-invalid={errors.name ? true : undefined}
                    />
                    <InputError message={errors.name} />
                </div>
                <NumberField
                    id="car-year"
                    name="year"
                    label="Tahun"
                    defaultValue={car?.year ?? new Date().getFullYear()}
                    min={1990}
                    max={maxYear}
                    error={errors.year}
                />
            </div>

            <div className="grid gap-5 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="car-price">Harga dasar (OTR)</Label>
                    <MoneyInput
                        id="car-price"
                        name="base_price"
                        defaultValue={car?.base_price}
                        error={errors.base_price}
                    />
                </div>
                <SelectField
                    id="car-showroom"
                    name="showroom_id"
                    label="Showroom"
                    options={options.showrooms.map((showroom) => ({
                        value: String(showroom.id),
                        label: `${showroom.name} · ${showroom.city}`,
                    }))}
                    defaultValue={
                        car?.showroom_id ? String(car.showroom_id) : null
                    }
                    noneLabel="Tanpa showroom"
                    error={errors.showroom_id}
                />
            </div>

            <div className="grid gap-5 sm:grid-cols-3">
                <SelectField
                    id="car-condition"
                    name="condition"
                    label="Kondisi"
                    options={options.conditions}
                    defaultValue={car?.condition ?? 'new'}
                    error={errors.condition}
                />
                <NumberField
                    id="car-mileage"
                    name="mileage_km"
                    label="Jarak tempuh"
                    unit="km"
                    defaultValue={car?.mileage_km ?? 0}
                    min={0}
                    max={2_000_000}
                    error={errors.mileage_km}
                />
                <NumberField
                    id="car-stock"
                    name="stock"
                    label="Stok"
                    unit="unit"
                    defaultValue={car?.stock ?? 1}
                    min={0}
                    max={999}
                    error={errors.stock}
                />
            </div>

            <div className="grid gap-5 sm:grid-cols-2">
                {car ? (
                    <SelectField
                        // Remount when the server reverts the car to draft (last photo/color removed).
                        key={car.status}
                        id="car-status"
                        name="status"
                        label="Status"
                        options={options.statuses}
                        defaultValue={car.status}
                        error={errors.status}
                    />
                ) : (
                    <div className="grid gap-2">
                        <Label>Status</Label>
                        <p className="flex min-h-9 items-center rounded-md border border-dashed border-border px-3 py-1.5 text-sm text-muted-foreground">
                            Draft — tayangkan setelah foto & warna lengkap.
                        </p>
                        <input type="hidden" name="status" value="draft" />
                        <InputError message={errors.status} />
                    </div>
                )}
                <SelectField
                    id="car-badge"
                    name="badge"
                    label="Badge"
                    options={options.badges}
                    defaultValue={car?.badge ?? null}
                    noneLabel="Tanpa badge"
                    error={errors.badge}
                />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="car-description">Deskripsi</Label>
                <textarea
                    id="car-description"
                    name="description"
                    defaultValue={car?.description ?? ''}
                    maxLength={5000}
                    className={textareaClass}
                    aria-invalid={errors.description ? true : undefined}
                />
                <InputError message={errors.description} />
            </div>

            <div className="grid gap-2">
                <div className="flex items-center gap-3">
                    <Checkbox
                        id="car-featured"
                        checked={featured}
                        onCheckedChange={(checked) =>
                            setFeatured(checked === true)
                        }
                    />
                    <Label htmlFor="car-featured">
                        Tampilkan sebagai mobil unggulan di beranda
                    </Label>
                </div>
                <input
                    type="hidden"
                    name="is_featured"
                    value={featured ? '1' : '0'}
                />
                <InputError message={errors.is_featured} />
            </div>
        </div>
    );
}
