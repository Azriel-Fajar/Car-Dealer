import { Form, usePage } from '@inertiajs/react';
import { useState } from 'react';
import CarController from '@/actions/App/Http/Controllers/Dashboard/CarController';
import { ColorsTab } from '@/components/dashboard/cars/colors-tab';
import { HotspotsTab } from '@/components/dashboard/cars/hotspots-tab';
import { ImagesTab } from '@/components/dashboard/cars/images-tab';
import {
    INFO_FIELDS,
    InfoFields,
} from '@/components/dashboard/cars/info-fields';
import { OptionsTab } from '@/components/dashboard/cars/options-tab';
import {
    SPEC_FIELDS,
    SpecFields,
} from '@/components/dashboard/cars/spec-fields';
import type {
    CarDetail,
    CarFormOptions,
    CarTab,
} from '@/components/dashboard/cars/types';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';

const TABS: { value: CarTab; label: string }[] = [
    { value: 'info', label: 'Info' },
    { value: 'specs', label: 'Spesifikasi' },
    { value: 'colors', label: 'Warna' },
    { value: 'options', label: 'Opsi' },
    { value: 'images', label: 'Foto' },
    { value: 'hotspots', label: 'Hotspot' },
];

const FORM_TAB_FIELDS: Partial<Record<CarTab, string[]>> = {
    info: INFO_FIELDS,
    specs: SPEC_FIELDS,
};

const isFormTab = (tab: CarTab) => tab === 'info' || tab === 'specs';

const tabWithError = (errors: Record<string, string>): CarTab | null => {
    const keys = Object.keys(errors);

    for (const tab of ['info', 'specs'] as const) {
        if (FORM_TAB_FIELDS[tab]?.some((field) => keys.includes(field))) {
            return tab;
        }
    }

    return null;
};

/**
 * Info + Spesifikasi share one form (both panels stay mounted); the other
 * tabs need a saved car and are filled in by their own sections.
 */
export function CarForm({
    car,
    options,
    tab: initialTab,
}: {
    car?: CarDetail;
    options: CarFormOptions;
    tab: CarTab;
}) {
    const errors = (usePage().props.errors ?? {}) as Record<string, string>;
    const [tab, setTab] = useState<CarTab>(
        !car && !isFormTab(initialTab) ? 'info' : initialTab,
    );

    const selectTab = (next: CarTab) => {
        setTab(next);

        // Keep ?tab= in the address bar so reloads and redirect-back land here.
        const url = new URL(window.location.href);
        url.searchParams.set('tab', next);
        window.history.replaceState(window.history.state, '', url);
    };

    const handleError = (next: Record<string, string>) => {
        const target = tabWithError(next);

        if (target && !FORM_TAB_FIELDS[tab]?.some((field) => field in next)) {
            selectTab(target);
        }
    };

    return (
        <div className="grid gap-6">
            <div
                role="tablist"
                aria-label="Bagian data mobil"
                className="-mx-4 flex gap-1 overflow-x-auto overflow-y-hidden border-b border-border px-4 md:mx-0 md:px-0"
            >
                {TABS.map((item) => {
                    const disabled = !car && !isFormTab(item.value);
                    const hasError =
                        FORM_TAB_FIELDS[item.value]?.some(
                            (field) => field in errors,
                        ) ?? false;

                    return (
                        <button
                            key={item.value}
                            type="button"
                            role="tab"
                            aria-selected={tab === item.value}
                            disabled={disabled}
                            title={
                                disabled
                                    ? 'Simpan mobil dulu untuk mengisi bagian ini.'
                                    : undefined
                            }
                            onClick={() => selectTab(item.value)}
                            className={cn(
                                '-mb-px flex shrink-0 items-center gap-1.5 border-b-2 px-3 py-2.5 text-sm font-medium whitespace-nowrap transition-colors',
                                tab === item.value
                                    ? 'border-primary text-foreground'
                                    : 'border-transparent text-muted-foreground hover:text-foreground',
                                disabled &&
                                    'cursor-not-allowed opacity-50 hover:text-muted-foreground',
                            )}
                        >
                            {item.label}
                            {hasError && (
                                <span
                                    aria-label="Ada kesalahan"
                                    className="size-1.5 rounded-full bg-destructive"
                                />
                            )}
                        </button>
                    );
                })}
            </div>

            <Form
                {...(car
                    ? CarController.update.form(car)
                    : CarController.store.form())}
                options={{ preserveScroll: true }}
                onError={handleError}
                noValidate
                className={cn('grid gap-6', !isFormTab(tab) && 'hidden')}
            >
                {({ processing, errors: formErrors }) => (
                    <>
                        <section
                            hidden={tab !== 'info'}
                            className="rounded-2xl border border-border bg-card p-4 md:p-6"
                        >
                            <InfoFields
                                car={car}
                                options={options}
                                errors={formErrors}
                            />
                        </section>
                        <section
                            hidden={tab !== 'specs'}
                            className="rounded-2xl border border-border bg-card p-4 md:p-6"
                        >
                            <SpecFields
                                car={car}
                                options={options}
                                errors={formErrors}
                            />
                        </section>
                        <div className="flex justify-end">
                            <Button
                                type="submit"
                                className="w-full rounded-xl sm:w-auto"
                                disabled={processing}
                            >
                                {processing && <Spinner />}
                                {car ? 'Simpan perubahan' : 'Simpan draft'}
                            </Button>
                        </div>
                    </>
                )}
            </Form>

            {car && tab === 'colors' && <ColorsTab car={car} />}
            {car && tab === 'options' && (
                <OptionsTab car={car} groups={options.optionGroups} />
            )}
            {car && tab === 'images' && <ImagesTab car={car} />}

            {car && tab === 'hotspots' && (
                <HotspotsTab
                    car={car}
                    onOpenImages={() => selectTab('images')}
                />
            )}
        </div>
    );
}
