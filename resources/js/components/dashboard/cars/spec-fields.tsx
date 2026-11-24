import { useState } from 'react';
import {
    NumberField,
    SelectField,
} from '@/components/dashboard/cars/form-controls';
import type {
    CarDetail,
    CarFormOptions,
} from '@/components/dashboard/cars/types';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export const SPEC_FIELDS = [
    'fuel_type',
    'transmission',
    'engine',
    'power_hp',
    'torque_nm',
    'accel_0_100',
    'top_speed',
    'range_km',
    'seats',
];

export function SpecFields({
    car,
    options,
    errors,
}: {
    car?: CarDetail;
    options: CarFormOptions;
    errors: Record<string, string>;
}) {
    const [fuelType, setFuelType] = useState(car?.fuel_type ?? 'petrol');
    const electric = fuelType === 'electric';

    return (
        <div className="grid gap-5">
            <div className="grid gap-5 sm:grid-cols-2">
                <SelectField
                    id="car-fuel"
                    name="fuel_type"
                    label="Bahan bakar"
                    options={options.fuelTypes}
                    defaultValue={fuelType}
                    error={errors.fuel_type}
                    onValueChange={setFuelType}
                />
                <SelectField
                    id="car-transmission"
                    name="transmission"
                    label="Transmisi"
                    options={options.transmissions}
                    defaultValue={car?.transmission ?? 'automatic'}
                    error={errors.transmission}
                />
            </div>

            <div className="grid gap-5 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="car-engine">
                        Mesin
                        <span className="ml-1 font-normal text-muted-foreground">
                            (opsional)
                        </span>
                    </Label>
                    <Input
                        id="car-engine"
                        name="engine"
                        defaultValue={car?.engine ?? ''}
                        maxLength={40}
                        placeholder={
                            electric ? 'Dual motor' : '2.0L Turbo 4-silinder'
                        }
                        aria-invalid={errors.engine ? true : undefined}
                    />
                    <InputError message={errors.engine} />
                </div>
                <NumberField
                    id="car-range"
                    name="range_km"
                    label={
                        electric
                            ? 'Jarak tempuh baterai'
                            : 'Jarak tempuh baterai (opsional)'
                    }
                    unit="km"
                    defaultValue={car?.range_km}
                    min={1}
                    max={2000}
                    required={electric}
                    error={errors.range_km}
                />
            </div>

            <div className="grid grid-cols-2 gap-5 sm:grid-cols-3">
                <NumberField
                    id="car-power"
                    name="power_hp"
                    label="Tenaga"
                    unit="hp"
                    defaultValue={car?.power_hp}
                    min={1}
                    max={2000}
                    error={errors.power_hp}
                />
                <NumberField
                    id="car-torque"
                    name="torque_nm"
                    label="Torsi"
                    unit="Nm"
                    defaultValue={car?.torque_nm}
                    min={1}
                    max={3000}
                    error={errors.torque_nm}
                />
                <NumberField
                    id="car-top-speed"
                    name="top_speed"
                    label="Top speed"
                    unit="km/j"
                    defaultValue={car?.top_speed}
                    min={1}
                    max={500}
                    error={errors.top_speed}
                />
                <NumberField
                    id="car-accel"
                    name="accel_0_100"
                    label="0–100 km/j"
                    unit="detik"
                    defaultValue={car?.accel_0_100}
                    min={1}
                    max={30}
                    step={0.1}
                    required={false}
                    error={errors.accel_0_100}
                />
                <NumberField
                    id="car-seats"
                    name="seats"
                    label="Kursi"
                    defaultValue={car?.seats ?? 5}
                    min={2}
                    max={9}
                    error={errors.seats}
                />
            </div>
        </div>
    );
}
