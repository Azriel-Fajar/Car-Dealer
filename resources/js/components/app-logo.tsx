import { usePage } from '@inertiajs/react';

export default function AppLogo() {
    const { name } = usePage().props;

    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded-md bg-primary text-primary-foreground">
                <span
                    aria-hidden="true"
                    className="size-2.5 rotate-45 bg-ember"
                />
            </div>
            <div className="ml-1 grid flex-1 text-left">
                <span className="truncate font-display text-base leading-none font-extrabold tracking-[0.06em] uppercase">
                    {name}
                </span>
                <span className="mt-1 truncate label-spec text-[0.625rem] leading-none text-muted-foreground">
                    Dashboard
                </span>
            </div>
        </>
    );
}
