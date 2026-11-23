import { ImageIcon, Upload } from 'lucide-react';
import { useEffect, useId, useRef, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';

export function ImageInput({
    name,
    currentUrl,
    accept,
    error,
}: {
    name: string;
    currentUrl?: string | null;
    accept: string;
    error?: string;
}) {
    const id = useId();
    const inputRef = useRef<HTMLInputElement>(null);
    const [preview, setPreview] = useState<string | null>(null);
    const [fileName, setFileName] = useState<string | null>(null);

    useEffect(
        () => () => {
            if (preview) {
                URL.revokeObjectURL(preview);
            }
        },
        [preview],
    );

    const handleChange = (file: File | undefined) => {
        setPreview(file ? URL.createObjectURL(file) : null);
        setFileName(file?.name ?? null);
    };

    const shown = preview ?? currentUrl ?? null;

    return (
        <div className="grid gap-2">
            <div className="flex items-center gap-4">
                <label
                    htmlFor={id}
                    className="flex size-20 shrink-0 cursor-pointer items-center justify-center overflow-hidden rounded-xl border border-dashed border-border bg-muted text-muted-foreground"
                >
                    {shown ? (
                        <img
                            src={shown}
                            alt="Pratinjau"
                            className="size-full object-contain p-2"
                        />
                    ) : (
                        <ImageIcon aria-hidden="true" className="size-6" />
                    )}
                </label>
                <div className="grid min-w-0 gap-1">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        className="w-fit"
                        onClick={() => inputRef.current?.click()}
                    >
                        <Upload />
                        {shown ? 'Ganti gambar' : 'Pilih gambar'}
                    </Button>
                    <p className="truncate text-xs text-muted-foreground">
                        {fileName ?? 'Belum ada file baru dipilih.'}
                    </p>
                </div>
            </div>
            <input
                ref={inputRef}
                id={id}
                type="file"
                name={name}
                accept={accept}
                className="sr-only"
                aria-invalid={error ? true : undefined}
                onChange={(event) => handleChange(event.target.files?.[0])}
            />
            <InputError message={error} />
        </div>
    );
}
