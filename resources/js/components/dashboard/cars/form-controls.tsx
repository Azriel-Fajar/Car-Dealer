import { useState } from 'react';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

const NONE = '__none__';

/**
 * Radix Select cannot hold an empty value, so the submitted value lives in a
 * hidden input ('' when a nullable field is cleared).
 */
export function SelectField({
    id,
    name,
    label,
    options,
    defaultValue,
    placeholder,
    noneLabel,
    error,
    onValueChange,
}: {
    id: string;
    name: string;
    label: string;
    options: { value: string; label: string }[];
    defaultValue?: string | null;
    placeholder?: string;
    /** When set, the field is nullable and this is the "empty" choice. */
    noneLabel?: string;
    error?: string;
    onValueChange?: (value: string) => void;
}) {
    const [value, setValue] = useState(defaultValue ?? '');

    const handleChange = (next: string) => {
        const resolved = next === NONE ? '' : next;
        setValue(resolved);
        onValueChange?.(resolved);
    };

    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>{label}</Label>
            <Select
                value={value === '' && noneLabel ? NONE : value}
                onValueChange={handleChange}
            >
                <SelectTrigger
                    id={id}
                    className="w-full"
                    aria-invalid={error ? true : undefined}
                >
                    <SelectValue placeholder={placeholder ?? 'Pilih…'} />
                </SelectTrigger>
                <SelectContent>
                    {noneLabel && (
                        <SelectItem value={NONE}>{noneLabel}</SelectItem>
                    )}
                    {options.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            <input type="hidden" name={name} value={value} />
            <InputError message={error} />
        </div>
    );
}

export function NumberField({
    id,
    name,
    label,
    defaultValue,
    min,
    max,
    step,
    unit,
    required = true,
    error,
}: {
    id: string;
    name: string;
    label: string;
    defaultValue?: number | string | null;
    min?: number;
    max?: number;
    step?: number;
    unit?: string;
    required?: boolean;
    error?: string;
}) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>
                {label}
                {unit && (
                    <span className="ml-1 font-normal text-muted-foreground">
                        ({unit})
                    </span>
                )}
            </Label>
            <Input
                id={id}
                name={name}
                type="number"
                inputMode={step && step < 1 ? 'decimal' : 'numeric'}
                defaultValue={defaultValue ?? ''}
                min={min}
                max={max}
                step={step}
                required={required}
                className="tabular-nums"
                aria-invalid={error ? true : undefined}
            />
            <InputError message={error} />
        </div>
    );
}
