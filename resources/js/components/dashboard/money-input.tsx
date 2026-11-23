import { useState } from 'react';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { formatRupiah } from '@/lib/format';

export function MoneyInput({
    name,
    defaultValue,
    error,
    id,
}: {
    name: string;
    defaultValue?: number;
    error?: string;
    id?: string;
}) {
    const [digits, setDigits] = useState(
        defaultValue === undefined ? '' : String(Math.round(defaultValue)),
    );

    const handleChange = (raw: string) => {
        setDigits(raw.replace(/\D/g, '').replace(/^0+(?=\d)/, ''));
    };

    return (
        <div className="grid gap-2">
            <Input
                id={id}
                type="text"
                inputMode="numeric"
                autoComplete="off"
                value={digits === '' ? '' : formatRupiah(Number(digits))}
                onChange={(event) => handleChange(event.target.value)}
                placeholder="Rp 0"
                aria-invalid={error ? true : undefined}
            />
            <input type="hidden" name={name} value={digits} />
            <InputError message={error} />
        </div>
    );
}
