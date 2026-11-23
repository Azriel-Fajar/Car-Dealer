function group(n: number): string {
    return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

function sign(amount: number): string {
    return amount < 0 ? '-' : '';
}

export function formatRupiah(amount: number): string {
    const rounded = Math.round(amount);

    return `${sign(rounded)}Rp ${group(Math.abs(rounded))}`;
}

export function formatRupiahShort(amount: number): string {
    const rounded = Math.round(amount);
    const a = Math.abs(rounded);

    if (a < 1_000_000) {
        return formatRupiah(rounded);
    }

    let unit = a >= 1_000_000_000 ? 1_000_000_000 : 1_000_000;
    let tenths = Math.floor((a * 10 + unit / 2) / unit);

    if (unit === 1_000_000 && tenths >= 10_000) {
        unit = 1_000_000_000;
        tenths = Math.floor((a * 10 + unit / 2) / unit);
    }

    const rest = tenths % 10;
    const text =
        group(Math.floor(tenths / 10)) + (rest !== 0 ? `,${rest}` : '');

    return `${sign(rounded)}Rp ${text} ${unit === 1_000_000_000 ? 'M' : 'jt'}`;
}
