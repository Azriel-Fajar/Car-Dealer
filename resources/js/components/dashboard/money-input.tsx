import { useState } from 'react';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { formatRupiah } from '@/lib/format';

export function MoneyInput({
    name,
    defaultValue,
    error,
    id,
    allowNegative = false,
}: {
    name: string;
    defaultValue?: number;
    error?: string;
    id?: string;
    /** Accept a leading "-" (e.g. a color that lowers the price). */
    allowNegative?: boolean;
}) {
    const [digits, setDigits] = useState(
        defaultValue === undefined
            ? ''
            : String(Math.abs(Math.round(defaultValue))),
    );
    const [negative, setNegative] = useState(
        allowNegative && (defaultValue ?? 0) < 0,
    );

    const handleChange = (raw: string) => {
        setNegative(allowNegative && raw.trimStart().startsWith('-'));
        setDigits(raw.replace(/\D/g, '').replace(/^0+(?=\d)/, ''));
    };

    const sign = negative ? '-' : '';
    const shown =
        digits === '' ? sign : formatRupiah(Number(`${sign}${digits}`));

    return (
        <div className="grid gap-2">
            <Input
                id={id}
                type="text"
                inputMode={allowNegative ? 'text' : 'numeric'}
                autoComplete="off"
                value={shown}
                onChange={(event) => handleChange(event.target.value)}
                placeholder="Rp 0"
                aria-invalid={error ? true : undefined}
            />
            <input
                type="hidden"
                name={name}
                value={digits === '' ? '' : `${sign}${digits}`}
            />
            <InputError message={error} />
        </div>
    );
}
